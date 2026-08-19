<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureActivated;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureUserIsTechnician;
use App\Http\Middleware\EnsureUserNotBlocked;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\SetWebsiteLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'activated' => EnsureActivated::class,
            'admin' => EnsureIsAdmin::class,
            'permission' => CheckPermission::class,
            'not-blocked' => EnsureUserNotBlocked::class,
            'technician' => EnsureUserIsTechnician::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin/*') ? route('admin.login') : route('login')
        );

        $middleware->web(append: [
            EnsureActivated::class,
            SetWebsiteLocale::class,
            SetSecurityHeaders::class,
            EnsureUserNotBlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Uniform JSON error envelope for all API routes
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            [$message, $status, $errors] = match (true) {
                $e instanceof ValidationException => [
                    'Validation failed.',
                    422,
                    $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    'Unauthenticated.',
                    401,
                    null,
                ],
                $e instanceof AccessDeniedHttpException => [
                    'You do not have permission to perform this action.',
                    403,
                    null,
                ],
                // Policies guarding another user's records deny with denyAsNotFound(), so the
                // API never reveals that a record exists. That surfaces here as a plain
                // HttpException carrying a 404, not a NotFoundHttpException.
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException,
                $e instanceof HttpExceptionInterface && $e->getStatusCode() === 404 => [
                    'The requested resource was not found.',
                    404,
                    null,
                ],
                $e instanceof TooManyRequestsHttpException => [
                    'Too many requests. Please slow down.',
                    429,
                    null,
                ],
                default => [
                    app()->hasDebugModeEnabled() ? $e->getMessage() : 'An unexpected error occurred.',
                    method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                    null,
                ],
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ], $status);
        });
    })->create();
