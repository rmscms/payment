<?php

namespace RMS\Payment\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use RMS\Payment\Models\PaymentDriver;

class InstallDriverCommand extends Command
{
    protected $signature = 'payment:install-driver {driver? : Driver key defined in payment config}';

    protected $description = 'Install payment driver assets (such as migrations) based on config.';

    public function handle(): int
    {
        $driver = $this->argument('driver') ?: config('payment.default');
        $gateways = config('payment.gateways', []);

        if (!$driver || !array_key_exists($driver, $gateways)) {
            $this->error("Driver [{$driver}] is not defined in config/payment.php.");

            return self::FAILURE;
        }

        $config = $gateways[$driver];
        $paths = Arr::wrap($config['migrations'] ?? []);

        $this->runBaseMigrations();

        if (empty($paths)) {
            $this->info("Driver [{$driver}] does not define any migrations.");
        } else {
            foreach ($paths as $path) {
                $migratePath = $this->normalizePath($path);

                if (!file_exists($migratePath)) {
                    $this->warn("Skipping missing migration path: {$migratePath}");
                    continue;
                }

                $this->info("Running migrations for driver [{$driver}] from: {$migratePath}");
                $this->call('migrate', [
                    '--path' => $this->relativePath($migratePath),
                    '--force' => true,
                ]);
            }
        }

        $this->syncDriverRecord($driver, $config);

        $this->info("Driver [{$driver}] installation finished.");

        return self::SUCCESS;
    }

    protected function normalizePath(string $path): string
    {
        if (str_starts_with($path, base_path())) {
            return $path;
        }

        return base_path($path);
    }

    protected function relativePath(string $absolute): string
    {
        return ltrim(str_replace(base_path(), '', $absolute), DIRECTORY_SEPARATOR);
    }

    protected function runBaseMigrations(): void
    {
        $basePath = $this->relativePath($this->normalizePath('packages/rms/payment/database/migrations'));
        $this->call('migrate', [
            '--path' => $basePath,
            '--force' => true,
        ]);
    }

    protected function syncDriverRecord(string $driver, array $config): void
    {
        if (!Schema::hasTable('payment_drivers')) {
            return;
        }

        PaymentDriver::updateOrCreate(
            ['driver' => $driver],
            [
                'title' => $config['title'] ?? ucfirst($driver),
                'slug' => $config['slug'] ?? $driver,
                'description' => $config['description'] ?? null,
                'logo' => $config['logo'] ?? null,
                'documentation_url' => $config['documentation_url'] ?? null,
                'sort_order' => $config['sort_order'] ?? 0,
                'is_active' => $config['active'] ?? true,
                'settings' => $config['settings'] ?? null,
            ]
        );
    }
}

