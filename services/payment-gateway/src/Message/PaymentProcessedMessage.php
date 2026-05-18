<?php

declare(strict_types=1);

namespace App\Message;

final class PaymentProcessedMessage
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $correlationId,
        public readonly float $amount,
        public readonly bool $highRisk,
    ) {}
}
