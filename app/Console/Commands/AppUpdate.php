<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

// NB: the target version option is `--release`, not `--version`. Symfony Console
// reserves `--version` globally, so a command that declares it never runs — the
// console just prints the framework version and exits 0.
#[Signature('app:update
    {--release= : Target version to update to}
    {--skip-backup : Skip the pre-update backup (not recommended)}
    {--skip-integrity : Skip verification of the release file checksums}
    {--force : Proceed even though a previous update was interrupted}')]
#[Description('Safely update the application: verify integrity, back up, migrate, and recover cleanly on failure.')]
class AppUpdate extends Command
{
    public function handle(): int
    {
        $targetVersion = $this->option('release') ?? config('installer.app_version', '1.0.0');
        $previousVersion = $this->setting('app_version');

        $this->components->twoColumnDetail('Previous version', $previousVersion ?? 'none');
        $this->components->twoColumnDetail('Target version', $targetVersion);

        if (! $this->guardAgainstInterruptedUpdate()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-integrity') && ! $this->verifyIntegrity()) {
            return self::FAILURE;
        }

        $backupTaken = false;

        if (! $this->option('skip-backup')) {
            if (! $this->runStep('Backing up before update', 'backup:run', ['--only-db' => true])) {
                $this->components->error('Backup failed — aborting before any changes were made. Re-run with --skip-backup to override.');

                return self::FAILURE;
            }

            $backupTaken = true;
        }

        // From here on the app is mid-update. The marker lets a later run detect that
        // this one died partway (power loss, killed process) instead of silently
        // continuing from an unknown state.
        $this->markUpdateInProgress($previousVersion, $targetVersion);
        Artisan::call('down', ['--render' => 'errors::503']);

        try {
            foreach ($this->updateSteps() as $description => [$command, $arguments]) {
                $this->runStepOrFail($description, $command, $arguments);
            }
        } catch (Throwable $exception) {
            return $this->recover($exception, $backupTaken);
        } finally {
            Artisan::call('up');
        }

        $this->settings([
            'app_version' => $targetVersion,
            'last_update_run_at' => now()->toISOString(),
        ]);
        $this->clearUpdateInProgress();

        $this->components->info("Application updated to v{$targetVersion} successfully.");

        return self::SUCCESS;
    }

    /**
     * The ordered steps that make up an update. Each entry is
     * description => [artisan command, arguments].
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    protected function updateSteps(): array
    {
        return [
            'Running migrations' => ['migrate', ['--force' => true]],
            'Clearing cache' => ['optimize:clear', []],
            'Caching config' => ['optimize', []],
            'Caching events' => ['event:cache', []],
            'Caching routes' => ['route:cache', []],
            'Caching views' => ['view:cache', []],
        ];
    }

    /**
     * A previous run that never finished leaves the app in an unknown state. Refuse to
     * stack another update on top of it unless the operator explicitly forces it.
     */
    private function guardAgainstInterruptedUpdate(): bool
    {
        $inProgress = $this->setting('update_in_progress');

        if (! $inProgress) {
            return true;
        }

        $this->components->error('A previous update did not finish (started '.$inProgress.').');
        $this->components->warn('Restore your pre-update backup (`php artisan backup:list` to find it), then re-run with --force once the app is back in a known-good state.');

        if (! $this->option('force')) {
            return false;
        }

        $this->components->warn('--force supplied: continuing over the interrupted update.');

        return true;
    }

    /**
     * Verify shipped files against the checksums in the release manifest, so a corrupt
     * or partial upload is caught before it can be migrated against.
     *
     * The manifest is optional: releases that ship without one simply skip the check.
     */
    private function verifyIntegrity(): bool
    {
        $manifestPath = base_path('release-manifest.json');

        if (! file_exists($manifestPath)) {
            $this->components->warn('No release-manifest.json found — skipping file integrity verification.');

            return true;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || $manifest === []) {
            $this->components->error('release-manifest.json is present but not valid JSON.');

            return false;
        }

        $missing = [];
        $corrupt = [];

        foreach ($manifest as $relativePath => $expectedHash) {
            $path = base_path($relativePath);

            if (! file_exists($path)) {
                $missing[] = $relativePath;

                continue;
            }

            if (! hash_equals((string) $expectedHash, hash_file('sha256', $path))) {
                $corrupt[] = $relativePath;
            }
        }

        if ($missing === [] && $corrupt === []) {
            $this->components->task('Verifying file integrity ('.count($manifest).' files)', fn () => true);

            return true;
        }

        $this->components->error('File integrity check failed — the upload looks incomplete or corrupted.');

        foreach (array_slice($missing, 0, 10) as $file) {
            $this->components->twoColumnDetail($file, '<fg=red>missing</>');
        }

        foreach (array_slice($corrupt, 0, 10) as $file) {
            $this->components->twoColumnDetail($file, '<fg=red>checksum mismatch</>');
        }

        $this->components->warn('Re-upload the release files and try again, or pass --skip-integrity to override.');

        return false;
    }

    /**
     * Bring the app back to a usable state and tell the operator exactly how to recover.
     */
    private function recover(Throwable $exception, bool $backupTaken): int
    {
        $this->components->error('Update failed: '.$exception->getMessage());

        // Route/config/view caches may hold half-updated state; a cleared cache is
        // always safe to boot from, so make sure we don't leave a poisoned one behind.
        Artisan::call('optimize:clear');

        if ($backupTaken) {
            $this->components->warn('A pre-update database backup was taken. Restore it with the archive listed by `php artisan backup:list`, then re-run `php artisan app:update --force`.');
        } else {
            $this->components->warn('No backup was taken (--skip-backup). Restore your own backup before re-running.');
        }

        $this->components->warn('The update marker has been left in place so the next run knows this attempt did not complete.');

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function runStepOrFail(string $description, string $command, array $arguments = []): void
    {
        if (! $this->runStep($description, $command, $arguments)) {
            throw new RuntimeException("Step failed: {$command}");
        }
    }

    /**
     * Run an Artisan command as a task, treating a non-zero exit code as a real failure.
     *
     * Note: passing Artisan::call() straight into components->task() is wrong — the task
     * component only renders FAIL for the value 2, so an exit code of 1 (the usual failure
     * code) would be reported as DONE. Compare the exit code explicitly instead.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function runStep(string $description, string $command, array $arguments = []): bool
    {
        $succeeded = true;

        $this->components->task($description, function () use ($command, $arguments, &$succeeded): bool {
            try {
                $succeeded = Artisan::call($command, $arguments) === self::SUCCESS;
            } catch (Throwable $exception) {
                $this->newLine();
                $this->components->error($exception->getMessage());
                $succeeded = false;
            }

            return $succeeded;
        });

        return $succeeded;
    }

    private function markUpdateInProgress(?string $previousVersion, string $targetVersion): void
    {
        $this->settings([
            'update_in_progress' => now()->toISOString(),
            'update_from_version' => (string) $previousVersion,
            'update_to_version' => $targetVersion,
        ]);
    }

    private function clearUpdateInProgress(): void
    {
        DB::table('settings')->whereIn('key', ['update_in_progress', 'update_from_version', 'update_to_version'])->delete();
    }

    private function setting(string $key): ?string
    {
        return DB::table('settings')->where('key', $key)->value('value');
    }

    /**
     * @param  array<string, string>  $values
     */
    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => 'system', 'type' => 'string']
            );
        }
    }
}
