<?php

declare(strict_types=1);

namespace App\Message;

final readonly class PaymentNotificationMessage
{
    public function __construct(
        public string $transactionId,
        public string $correlationId,
        public float  $amount,
        public string $status,
        public string $recipientEmail,
    ) {}
}
