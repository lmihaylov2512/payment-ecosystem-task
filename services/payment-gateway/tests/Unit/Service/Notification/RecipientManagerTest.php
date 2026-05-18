<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Notification;

use App\Service\Notification\AdminRecipientStrategy;
use App\Service\Notification\CustomerRecipientStrategy;
use App\Service\Notification\RecipientManager;
use PHPUnit\Framework\TestCase;

class RecipientManagerTest extends TestCase
{
    private RecipientManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RecipientManager(
            new CustomerRecipientStrategy(),
            new AdminRecipientStrategy('admin@payment-ecosystem.local'),
        );
    }

    public function testResolvesCustomerStrategyWhenNotHighRisk(): void
    {
        $strategy = $this->manager->resolve(false);

        $this->assertInstanceOf(CustomerRecipientStrategy::class, $strategy);
    }

    public function testResolvesAdminStrategyWhenHighRisk(): void
    {
        $strategy = $this->manager->resolve(true);

        $this->assertInstanceOf(AdminRecipientStrategy::class, $strategy);
    }
}
