<?php

namespace RMS\Payment;

use Illuminate\Support\ServiceProvider;
use RMS\Payment\Support\GatewayManager;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payment.php', 'payment');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \RMS\Payment\Console\Commands\PaymentInstallCommand::class,
            ]);
        }

        $this->app->singleton(GatewayManager::class, function ($app) {
            return new GatewayManager(
                $app,
                $app['config']->get('payment', [])
            );
        });
        $this->app->alias(GatewayManager::class, 'payment.manager');

        $this->app->singleton(PaymentClient::class, function ($app) {
            return new PaymentClient(
                $app->make(GatewayManager::class),
                $app['config']->get('payment', [])
            );
        });
        $this->app->alias(PaymentClient::class, 'payment.client');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'payment');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payment');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/payment.php' => config_path('payment.php'),
        ], 'payment-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \RMS\Payment\Console\InstallDriverCommand::class,
            ]);
        }

        if (config('payment.admin.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }
    }
}

