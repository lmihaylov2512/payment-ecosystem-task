<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Notification;

use App\Entity\Transaction;
use App\Service\Notification\AdminRecipientStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AdminRecipientStrategyTest extends TestCase
{
    private function makeTransaction(string $userEmail = 'customer@example.com'): Transaction
    {
        return new Transaction(
            transactionId: Uuid::v7(),
            userId:        Uuid::v4(),
            userEmail:     $userEmail,
            amount:        100.0,
            currency:      'EUR',
            paymentMethod: 'card',
            requestId:     'req-001',
            correlationId: Uuid::v7(),
        );
    }

    public function testResolvesInjectedAdminEmail(): void
    {
        $strategy = new AdminRecipientStrategy('admin@payment-ecosystem.local');

        $this->assertSame(
            'admin@payment-ecosystem.local',
            $strategy->resolveEmail($this->makeTransaction()),
        );
    }

    public function testIgnoresTransactionUserEmail(): void
    {
        $strategy = new AdminRecipientStrategy('admin@payment-ecosystem.local');

        $this->assertSame(
            'admin@payment-ecosystem.local',
            $strategy->resolveEmail($this->makeTransaction('someone-else@example.com')),
        );
    }
}
