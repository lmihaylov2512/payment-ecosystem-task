<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Flagged  = 'flagged';
}
