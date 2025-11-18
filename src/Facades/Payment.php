<?php

namespace RMS\Payment\Facades;

use Illuminate\Support\Facades\Facade;
use RMS\Payment\PaymentClient;

/**
 * @method static \RMS\Payment\DTO\InitializationResult start(\RMS\Payment\DTO\PaymentRequest $request, ?string $driver = null)
 * @method static \RMS\Payment\DTO\VerificationResult verify(array $payload, ?string $driver = null)
 * @method static array gateways()
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PaymentClient::class;
    }
}

