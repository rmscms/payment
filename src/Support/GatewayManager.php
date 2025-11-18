<?php

namespace RMS\Payment\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use RMS\Payment\Contracts\Gateway;
use RuntimeException;

class GatewayManager
{
    protected array $drivers = [];

    public function __construct(
        protected Container $app,
        protected array $config = [],
    ) {
    }

    public function getDefaultDriver(): string
    {
        $driver = $this->config['default'] ?? null;

        if (!$driver) {
            throw new RuntimeException('Payment driver is not configured.');
        }

        return $driver;
    }

    public function driver(?string $driver = null): Gateway
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    protected function createDriver(string $driver): Gateway
    {
        $gateways = $this->config['gateways'] ?? [];
        $config = Arr::get($gateways, $driver);

        if (!$config) {
            throw new RuntimeException("Payment driver [{$driver}] is not defined.");
        }

        $class = $config['driver'] ?? null;
        if (!$class || !class_exists($class)) {
            throw new RuntimeException("Payment driver class for [{$driver}] is invalid.");
        }

        $instance = $this->app->make($class, ['config' => $config]);

        if (!$instance instanceof Gateway) {
            throw new RuntimeException("Payment driver [$driver] must implement ".Gateway::class);
        }

        return $instance;
    }

    public function getConfig(?string $driver = null): array
    {
        if ($driver === null) {
            return $this->config;
        }

        return $this->config['gateways'][$driver] ?? [];
    }

}

