<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Transaction;
use App\Enum\TransactionStatus;
use App\Message\PaymentProcessedMessage;
use App\MessageHandler\PaymentProcessedHandler;
use App\Repository\TransactionRepository;
use App\Service\Notification\NotificationService;
use App\Service\Notification\RecipientInterface;
use App\Service\Notification\RecipientManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentProcessedHandlerTest extends TestCase
{
    private TransactionRepository $repository;
    private EntityManagerInterface&MockObject $entityManager;
    private NotificationService&MockObject $notificationService;
    private RecipientManager&MockObject $recipientManager;
    private PaymentProcessedHandler $handler;

    protected function setUp(): void
    {
        $this->repository          = $this->createStub(TransactionRepository::class);
        $this->entityManager       = $this->createMock(EntityManagerInterface::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->recipientManager    = $this->createMock(RecipientManager::class);

        $this->handler = new PaymentProcessedHandler(
            $this->repository,
            $this->entityManager,
            $this->notificationService,
            $this->recipientManager,
        );
    }

    private function makeRecipient(string $email): RecipientInterface
    {
        $recipient = $this->createStub(RecipientInterface::class);
        $recipient->method('resolveEmail')->willReturn($email);

        return $recipient;
    }

    public function testHighRiskSetsStatusFlaggedAndNotifiesViaResolvedRecipient(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $this->repository->method('findByTransactionId')->willReturn($transaction);

        $recipient = $this->makeRecipient('admin@payment-ecosystem.local');
        $this->recipientManager->expects($this->once())
            ->method('resolve')
            ->with(true)
            ->willReturn($recipient);

        $transaction->expects($this->once())->method('setStatus')->with(TransactionStatus::Flagged);
        $transaction->expects($this->never())->method('setAcceptedAt');
        $this->entityManager->expects($this->once())->method('flush');
        $this->notificationService->expects($this->once())
            ->method('dispatchPaymentNotification')
            ->with($transaction, 'admin@payment-ecosystem.local');

        ($this->handler)(new PaymentProcessedMessage('tx-id', 'corr-id', 100.0, true));
    }

    public function testLowRiskSetsStatusAcceptedAndNotifiesViaResolvedRecipient(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $this->repository->method('findByTransactionId')->willReturn($transaction);

        $recipient = $this->makeRecipient('customer@example.com');
        $this->recipientManager->expects($this->once())
            ->method('resolve')
            ->with(false)
            ->willReturn($recipient);

        $transaction->expects($this->once())->method('setStatus')->with(TransactionStatus::Accepted);
        $transaction->expects($this->once())->method('setAcceptedAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->notificationService->expects($this->once())
            ->method('dispatchPaymentNotification')
            ->with($transaction, 'customer@example.com');

        ($this->handler)(new PaymentProcessedMessage('tx-id', 'corr-id', 100.0, false));
    }

    public function testSkipsUnknownTransaction(): void
    {
        $this->repository->method('findByTransactionId')->willReturn(null);

        $this->entityManager->expects($this->never())->method('flush');
        $this->recipientManager->expects($this->never())->method('resolve');
        $this->notificationService->expects($this->never())->method('dispatchPaymentNotification');

        ($this->handler)(new PaymentProcessedMessage('unknown-id', 'corr-id', 50.0, false));
    }
}