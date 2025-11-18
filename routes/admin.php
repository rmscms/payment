<?php

use Illuminate\Support\Facades\Route;
use RMS\Payment\Http\Controllers\Admin\PaymentDriversController;
use RMS\Payment\Http\Controllers\Admin\PaymentTransactionsController;

if (!class_exists(\RMS\Core\Helpers\RouteHelper::class)) {
    return;
}

$adminPrefix = config('cms.admin_url', 'admin');
$paymentPrefix = trim(config('payment.admin.prefix', 'payment'), '/');
$routeNamePrefix = trim(config('payment.admin.route_prefix', 'admin.payment'), '.');
$driversRoute = 'drivers';
$transactionsRoute = 'transactions';
$middleware = config('payment.admin.middleware', [
    'web',
    class_exists(\RMS\Core\Middleware\AdminAuthenticate::class)
        ? \RMS\Core\Middleware\AdminAuthenticate::class
        : 'auth:admin',
]);

Route::middleware($middleware)
    ->prefix($adminPrefix . '/' . $paymentPrefix)
    ->name($routeNamePrefix . '.')
    ->group(function () use ($driversRoute, $transactionsRoute) {
        \RMS\Core\Helpers\RouteHelper::adminResource(PaymentDriversController::class, $driversRoute, [
            'export' => false,
            'sort' => true,
            'filter' => true,
            'toggle_active' => true,
            'batch_actions' => [],
        ]);
        Route::post('drivers/sync-from-config', [PaymentDriversController::class, 'syncFromConfig'])
            ->name('drivers.sync');
        Route::resource('drivers', PaymentDriversController::class);

        \RMS\Core\Helpers\RouteHelper::adminResource(PaymentTransactionsController::class, $transactionsRoute, [
            'export' => true,
            'sort' => true,
            'filter' => true,
            'toggle_active' => false,
            'batch_actions' => [],
        ]);
        Route::resource('transactions', PaymentTransactionsController::class)->only(['index']);
    });

