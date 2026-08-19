<?php

namespace App\Http\Controllers;

use App\Models\Activation;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use App\Support\SettingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstallerController extends Controller
{
    public function __construct(private SettingRepository $settings) {}

    public function redirectToInstaller(): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('installer.requirements');
    }

    // ── Step 1: Requirements ────────────────────────────────────────────────

    public function requirements(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, config('installer.php_min_version'), '>=');

        $extensions = [];
        foreach (config('installer.required_extensions') as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }

        $folders = [];
        foreach (config('installer.writable_folders') as $folder) {
            $path = base_path($folder);
            $folders[$folder] = is_dir($path) && is_writable($path);
        }

        $passes = $phpOk
            && ! in_array(false, $extensions, true)
            && ! in_array(false, $folders, true);

        return view('installer.requirements', compact('phpVersion', 'phpOk', 'extensions', 'folders', 'passes'));
    }

    // ── Step 2: Database ────────────────────────────────────────────────────

    public function database(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        return view('installer.database');
    }

    public function saveDatabase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'numeric', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        $envContent = $this->buildInstallEnv(
            file_get_contents(base_path('.env.example')),
            $data,
            'base64:'.base64_encode(Str::random(32)),
        );

        file_put_contents(base_path('.env'), $envContent);

        Cache::flush();
        app()->loadEnvironmentFrom('.env');

        try {
            DB::purge();
            DB::reconnect();
            DB::connection()->getPdo();

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['db_connection' => 'Database connection failed: '.$e->getMessage()]);
        }

        return redirect()->route('installer.admin');
    }

    // ── Step 3: Admin Account ───────────────────────────────────────────────

    public function admin(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        return view('installer.admin');
    }

    public function saveAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'is_system' => true]
        );

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $superAdminRole->id,
            'verification_status' => 'verified',
        ]);

        auth()->login($admin);

        return redirect()->route('installer.settings');
    }

    // ── Step 4: Basic Settings ──────────────────────────────────────────────

    public function settings(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        $languages = Language::all();
        $currencies = Currency::all();

        return view('installer.settings', compact('languages', 'currencies'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'default_language' => ['required', 'integer', 'exists:languages,id'],
            'default_currency' => ['required', 'integer', 'exists:currencies,id'],
        ]);

        // The rest of the app reads these settings as ISO codes, not row IDs, so
        // resolve the selected id to its code before persisting.
        $this->settings->set('site_name', $data['site_name']);
        $this->settings->set('default_language', Language::find($data['default_language'])?->code ?? 'en');
        $this->settings->set('default_currency', Currency::find($data['default_currency'])?->code ?? 'USD');

        return redirect()->route('installer.license');
    }

    // ── Step 5: License Activation ──────────────────────────────────────────

    public function license(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        return view('installer.license');
    }

    public function verifyLicense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_code' => ['required', 'string', 'max:255'],
        ]);

        if (empty(config('installer.envato_personal_token'))) {
            if (! config('installer.allow_manual_activation')) {
                return back()->withInput()->withErrors([
                    'purchase_code' => 'License verification is not configured. Set ENVATO_PERSONAL_TOKEN in .env to verify purchase codes, or explicitly set INSTALLER_ALLOW_MANUAL_ACTIVATION=true for a local/demo install.',
                ]);
            }

            Activation::create([
                'purchase_code' => $data['purchase_code'],
                'verified_at' => now(),
                'payload' => ['manual_override' => true, 'note' => 'No Envato token configured; manually activated via INSTALLER_ALLOW_MANUAL_ACTIVATION.'],
            ]);

            return redirect()->route('installer.finish');
        }

        $response = Http::withToken(config('installer.envato_personal_token'))
            ->get(config('installer.envato_api_url'), [
                'code' => $data['purchase_code'],
            ]);

        if ($response->failed()) {
            return back()->withInput()->withErrors(['purchase_code' => 'Invalid or already used purchase code. Please verify and try again.']);
        }

        $body = $response->json();

        Activation::create([
            'purchase_code' => $data['purchase_code'],
            'envato_item_id' => $body['item']['id'] ?? null,
            'license_type' => $body['license'] ?? null,
            'buyer_email' => $body['buyer'] ?? null,
            'buyer_name' => $body['buyer'] ?? null,
            'verified_at' => now(),
            'expires_at' => isset($body['supported_until']) ? now()->parse($body['supported_until']) : null,
            'payload' => $body,
        ]);

        return redirect()->route('installer.finish');
    }

    // ── Step 6: Finish ──────────────────────────────────────────────────────

    public function finish(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        file_put_contents(storage_path('app/installed.lock'), now()->toISOString());

        return view('installer.finish');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isInstalled(): bool
    {
        return file_exists(storage_path('app/installed.lock')) || Activation::isActivated();
    }

    /**
     * Render the production .env from the example template.
     *
     * Values are rewritten by key (not by the template's default value) so a
     * changed .env.example default can never cause the user's input to be
     * silently dropped. A web-installer run is a live deployment, so the
     * production-safe flags are forced regardless of the template's local
     * defaults.
     *
     * @param  array{db_host: string, db_port: int|string, db_database: string, db_username: string, db_password?: string|null}  $data
     */
    public function buildInstallEnv(string $template, array $data, string $appKey): string
    {
        $replacements = [
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'APP_KEY' => $appKey,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'LOG_LEVEL' => 'error',
        ];

        foreach ($replacements as $key => $value) {
            $line = $key.'='.$this->encodeEnvValue((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $template = preg_match($pattern, $template)
                ? preg_replace($pattern, $line, $template)
                : rtrim($template, "\n")."\n".$line."\n";
        }

        return $template;
    }

    /**
     * Quote an .env value when it contains whitespace or characters that the
     * dotenv parser would otherwise mis-read (so DB passwords with spaces or
     * `#` survive intact).
     */
    private function encodeEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|[#"\'=]/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    public function checkDb(Request $request): JsonResponse
    {
        try {
            $config = $request->validate([
                'db_host' => ['required', 'string'],
                'db_port' => ['required', 'numeric'],
                'db_database' => ['required', 'string'],
                'db_username' => ['required', 'string'],
                'db_password' => ['nullable', 'string'],
            ]);

            config(['database.connections.install_check' => [
                'driver' => 'mysql',
                'host' => $config['db_host'],
                'port' => $config['db_port'],
                'database' => $config['db_database'],
                'username' => $config['db_username'],
                'password' => $config['db_password'],
                'charset' => 'utf8mb4',
            ]]);

            DB::purge();
            DB::reconnect('install_check');
            DB::connection('install_check')->getPdo();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
