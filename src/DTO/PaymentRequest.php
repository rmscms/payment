<?php

namespace RMS\Payment\DTO;

class PaymentRequest
{
    public function __construct(
        public readonly string $orderId,
        public readonly int|float $amount,
        public readonly string $currency = 'IRT',
        public readonly ?string $callbackUrl = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerMobile = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null,
    ) {
    }
}

