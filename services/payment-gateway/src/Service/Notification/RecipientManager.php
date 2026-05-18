<?php

declare(strict_types=1);

namespace App\Service\Notification;

class RecipientManager
{
    public function __construct(
        private readonly CustomerRecipientStrategy $customerStrategy,
        private readonly AdminRecipientStrategy $adminStrategy,
    ) {}

    public function resolve(bool $highRisk): RecipientInterface
    {
        return $highRisk ? $this->adminStrategy : $this->customerStrategy;
    }
}
