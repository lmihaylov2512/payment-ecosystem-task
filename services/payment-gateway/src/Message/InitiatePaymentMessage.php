<?php

declare(strict_types=1);

namespace App\Message;

final class InitiatePaymentMessage
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $correlationId,
        public readonly float $amount,
        public readonly string $currency,
    ) {}
}
