<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\Transaction;

class AdminRecipientStrategy implements RecipientInterface
{
    public function __construct(
        private readonly string $adminEmail,
    ) {}

    public function resolveEmail(Transaction $transaction): string
    {
        return $this->adminEmail;
    }
}
