<?php

namespace RMS\Payment\Contracts;

use RMS\Payment\DTO\InitializationResult;
use RMS\Payment\DTO\PaymentRequest;
use RMS\Payment\DTO\VerificationResult;

interface Gateway
{
    public function initialize(PaymentRequest $request): InitializationResult;

    public function verify(array $payload): VerificationResult;
}

