<?php

namespace RMS\Payment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PaymentInstallCommand extends Command
{
    protected $signature = 'payment:install {--driver= : Optional driver key defined in payment config} {--skip-sidebar : Skip adding payment menu to CMS sidebar}';

    protected $description = 'Install RMS Payment package (config, migrations, drivers, admin menu).';

    public function handle(): int
    {
        $this->info('Installing RMS Payment package...');

        $this->publishConfig();
        $this->runMigrations();
        $this->installDriver();

        if (!$this->option('skip-sidebar')) {
            $this->updateSidebar();
        }

        $this->info('RMS Payment installed successfully.');

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->info('Publishing payment config...');
        Artisan::call('vendor:publish', [
            '--tag' => 'payment-config',
            '--force' => true,
        ]);
    }

    protected function runMigrations(): void
    {
        $this->info('Running payment migrations...');
        Artisan::call('migrate', [
            '--path' => $this->relativeMigrationPath(),
            '--force' => true,
        ]);
    }

    protected function installDriver(): void
    {
        $driver = $this->option('driver');
        $this->info(sprintf('Installing payment driver (%s)...', $driver ?: 'default'));

        $params = [];
        if (!empty($driver)) {
            $params['driver'] = $driver;
        }

        Artisan::call('payment:install-driver', $params);
    }

    protected function updateSidebar(): void
    {
        $sidebarPath = resource_path('views/vendor/cms/admin/layout/sidebar.blade.php');

        if (!File::exists($sidebarPath)) {
            $this->warn('Sidebar file not found. Publish CMS views before running payment:install to auto-insert menu.');
            return;
        }

        $content = File::get($sidebarPath);
        if (str_contains($content, '{{-- Payment Management --}}')) {
            $this->info('Payment menu already exists in sidebar. Skipping insertion.');
            return;
        }

        $stubPath = __DIR__ . '/../../../resources/stubs/payment-menu.blade.stub';
        if (!File::exists($stubPath)) {
            $this->error('Payment menu stub not found.');
            return;
        }

        $menu = "\n" . File::get($stubPath) . "\n";
        $updated = preg_replace('/(\s*<\/ul>)/', $menu . '$1', $content, 1);

        if ($updated === null) {
            $this->error('Failed to inject payment menu into sidebar.');
            return;
        }

        File::put($sidebarPath, $updated);
        $this->info('Payment menu added to admin sidebar.');
    }

    protected function relativeMigrationPath(): string
    {
        $absolute = realpath(__DIR__ . '/../../../database/migrations');

        if (!$absolute) {
            return 'vendor/rms/payment/database/migrations';
        }

        return ltrim(str_replace(base_path(), '', $absolute), DIRECTORY_SEPARATOR) ?: 'vendor/rms/payment/database/migrations';
    }
}
