<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\Transaction;

class CustomerRecipientStrategy implements RecipientInterface
{
    public function resolveEmail(Transaction $transaction): string
    {
        return $transaction->getUserEmail();
    }
}
