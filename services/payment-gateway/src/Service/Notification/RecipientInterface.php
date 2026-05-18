<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\Transaction;

interface RecipientInterface
{
    public function resolveEmail(Transaction $transaction): string;
}
