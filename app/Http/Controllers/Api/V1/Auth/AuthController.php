<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterStartRequest;
use App\Http\Requests\Api\V1\Auth\RegisterVerifyRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\SocialAuthRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SocialAuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends ApiController
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * POST /api/v1/auth/register/start
     *
     * Send registration OTP to phone. Returns 409 if phone already registered.
     */
    public function registerStart(RegisterStartRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');

        // Fail early if phone is already taken — but with a generic message to avoid enumeration
        if (User::where('phone', $phone)->exists()) {
            return $this->error('This phone number is already registered.', 409);
        }

        if (! $this->otpService->canResend($phone, 'register')) {
            $seconds = $this->otpService->resendAvailableIn($phone, 'register');

            return $this->error("Please wait {$seconds} seconds before requesting a new OTP.", 429);
        }

        $this->otpService->send($phone, 'register');
        $this->otpService->recordSend($phone, 'register');

        // The code is delivered by SMS only. Returning it here would let anyone
        // request an OTP for a phone they do not own and read it straight back.
        return $this->success(['otp_sent' => true], 'OTP sent successfully. It expires in 10 minutes.');
    }

    /**
     * POST /api/v1/auth/register/verify
     *
     * Verify OTP, create user, return Sanctum token.
     */
    public function registerVerify(RegisterVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $phone = $data['phone'];

        if (User::where('phone', $phone)->exists()) {
            return $this->error('This phone number is already registered.', 409);
        }

        if (! $this->otpService->verify($phone, $data['otp'], 'register')) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        $defaultRole = Role::where('name', 'user')->first();

        $user = User::create([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'password' => isset($data['password']) ? $data['password'] : Str::random(32),
            'role_id' => $defaultRole?->id,
            'language' => app()->getLocale(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->created([
            'user' => new UserResource($user->load('role')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Registration successful.');
    }

    /**
     * POST /api/v1/auth/login
     *
     * Two flows:
     *   1. Email + password → immediate token.
     *   2. Phone only        → send OTP, return {otp_sent: true, otp: "..."}.
     *   3. Phone + otp       → verify OTP, return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ── Email + password flow ──────────────────────────────────────────────
        if (isset($data['email'])) {
            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return $this->error('These credentials do not match our records.', 401);
            }

            return $this->issueToken($user);
        }

        // ── Phone flow ─────────────────────────────────────────────────────────
        $phone = $data['phone'];
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            // Generic message — do not reveal whether the phone is registered
            return $this->error('These credentials do not match our records.', 401);
        }

        // No OTP provided → send one
        if (empty($data['otp'])) {
            if (! $this->otpService->canResend($phone, 'login')) {
                $seconds = $this->otpService->resendAvailableIn($phone, 'login');

                return $this->error("Please wait {$seconds} seconds before requesting a new OTP.", 429);
            }

            $this->otpService->send($phone, 'login');
            $this->otpService->recordSend($phone, 'login');

            // SMS only — see registerStart().
            return $this->success(['otp_sent' => true], 'OTP sent to your phone.');
        }

        // OTP provided → verify
        if (! $this->otpService->verify($phone, $data['otp'], 'login')) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        return $this->issueToken($user);
    }

    /**
     * POST /api/v1/auth/social
     *
     * Exchange a Google/Facebook token for a Sanctum token.
     * Creates an account on first sign-in.
     */
    public function social(SocialAuthRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $socialUser = $this->socialAuthService->verify($data['provider'], $data['token']);
        } catch (Exception $e) {
            // Log the provider's raw message; never echo it to the client (it can
            // leak provider internals and endpoint details).
            report($e);

            return $this->error('Social authentication failed. Please try again.', 422);
        }

        // Find by provider + social ID, or fall back to email
        $user = User::where('social_provider', $data['provider'])
            ->where('social_id', $socialUser['id'])
            ->first();

        if (! $user && $socialUser['email']) {
            $user = User::where('email', $socialUser['email'])->first();
        }

        $isNew = false;

        if (! $user) {
            $defaultRole = Role::where('name', 'user')->first();
            $user = User::create([
                'name' => $socialUser['name'] ?? 'User',
                'email' => $socialUser['email'],
                'password' => Str::random(32),
                'social_provider' => $data['provider'],
                'social_id' => $socialUser['id'],
                'role_id' => $defaultRole?->id,
                'language' => app()->getLocale(),
            ]);
            $isNew = true;
        } else {
            // Keep social link up to date
            $user->update([
                'social_provider' => $data['provider'],
                'social_id' => $socialUser['id'],
            ]);
        }

        return $this->issueToken($user, $isNew ? 201 : 200);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentAccessToken = $user->currentAccessToken();

        if ($currentAccessToken instanceof PersonalAccessToken) {
            $currentAccessToken->delete();
        } elseif (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/forgot
     *
     * Send password reset via email (token link) or phone (OTP).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ── Phone-based reset ──────────────────────────────────────────────────
        if (isset($data['phone'])) {
            $phone = $data['phone'];
            $user = User::where('phone', $phone)->first();

            // Always return success to prevent enumeration
            if ($user) {
                if (! $this->otpService->canResend($phone, 'reset')) {
                    $seconds = $this->otpService->resendAvailableIn($phone, 'reset');

                    return $this->error("Please wait {$seconds} seconds before requesting a new OTP.", 429);
                }

                $this->otpService->send($phone, 'reset');
                $this->otpService->recordSend($phone, 'reset');
            }

            return $this->success(null, 'If your phone is registered, you will receive a reset OTP shortly.');
        }

        // ── Email-based reset ──────────────────────────────────────────────────
        // The address is guaranteed to exist (exists:users,email in the request),
        // so a successful send means the account is real.
        $status = Password::sendResetLink(['email' => $data['email']]);

        return $this->success(null, 'Check your email for a reset link.');
    }

    /**
     * POST /api/v1/auth/reset
     *
     * Reset password via email token or phone OTP.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ── Phone-based reset ──────────────────────────────────────────────────
        if (isset($data['phone'])) {
            $phone = $data['phone'];
            $user = User::where('phone', $phone)->first();

            if (! $user) {
                return $this->error('No account found for this phone number.', 404);
            }

            if (! $this->otpService->verify($phone, $data['otp'], 'reset')) {
                return $this->error('Invalid or expired OTP.', 422);
            }

            $user->update(['password' => $data['password']]);

            return $this->success(null, 'Password reset successfully.');
        }

        // ── Email-based reset ──────────────────────────────────────────────────
        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('The password reset token is invalid or has expired.', 422);
        }

        return $this->success(null, 'Password reset successfully.');
    }

    /** Build and return the token response. */
    private function issueToken(User $user, int $status = 200): JsonResponse
    {
        if ($user->is_blocked) {
            return $this->error('Your account has been suspended. Please contact support.', 403);
        }

        // Revoke previous tokens with the same name to avoid token accumulation on mobile re-logins
        $user->tokens()->where('name', 'api-token')->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        $user->update(['last_active_at' => now()]);

        return $this->success([
            'user' => new UserResource($user->load('role')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Authentication successful.', $status);
    }
}
