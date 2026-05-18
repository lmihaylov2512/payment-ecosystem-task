<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\Transaction;
use App\Message\{InitiatePaymentMessage, PaymentNotificationMessage};
use Symfony\Component\Messenger\MessageBusInterface;

readonly class NotificationService
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {}

    public function dispatchPaymentInitiation(Transaction $transaction): void
    {
        $this->bus->dispatch(new InitiatePaymentMessage(
            transactionId: $transaction->getTransactionId()->toString(),
            correlationId: $transaction->getCorrelationId()->toString(),
            amount: $transaction->getAmount(),
            currency: $transaction->getCurrency(),
        ));
    }

    public function dispatchPaymentNotification(Transaction $transaction, string $recipientEmail): void
    {
        $this->bus->dispatch(new PaymentNotificationMessage(
            transactionId: $transaction->getTransactionId()->toString(),
            correlationId: $transaction->getCorrelationId()->toString(),
            amount: $transaction->getAmount(),
            status: $transaction->getStatus()->value,
            recipientEmail: $recipientEmail,
        ));
    }
}
