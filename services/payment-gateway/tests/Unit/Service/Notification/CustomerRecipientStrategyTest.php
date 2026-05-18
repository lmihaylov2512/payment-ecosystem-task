<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Notification;

use App\Entity\Transaction;
use App\Service\Notification\CustomerRecipientStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CustomerRecipientStrategyTest extends TestCase
{
    public function testResolvesUserEmailFromTransaction(): void
    {
        $transaction = new Transaction(
            transactionId: Uuid::v7(),
            userId:        Uuid::v4(),
            userEmail:     'customer@example.com',
            amount:        100.0,
            currency:      'EUR',
            paymentMethod: 'card',
            requestId:     'req-001',
            correlationId: Uuid::v7(),
        );

        $strategy = new CustomerRecipientStrategy();

        $this->assertSame('customer@example.com', $strategy->resolveEmail($transaction));
    }
}
