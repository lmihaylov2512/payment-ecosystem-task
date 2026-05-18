<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Transaction;
use App\Enum\TransactionStatus;
use App\Message\PaymentProcessedMessage;
use App\MessageHandler\PaymentProcessedHandler;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentProcessedHandlerTest extends TestCase
{
    private TransactionRepository $repository;
    private EntityManagerInterface&MockObject $entityManager;
    private PaymentProcessedHandler $handler;

    protected function setUp(): void
    {
        $this->repository    = $this->createStub(TransactionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler       = new PaymentProcessedHandler($this->repository, $this->entityManager);
    }

    public function testSetsStatusToFlaggedWhenHighRisk(): void
    {
        $transaction = $this->createMock(Transaction::class);

        $this->repository->method('findByTransactionId')->willReturn($transaction);

        $transaction->expects($this->once())->method('setStatus')->with(TransactionStatus::Flagged);
        $transaction->expects($this->never())->method('setAcceptedAt');
        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)(new PaymentProcessedMessage('tx-id', 'corr-id', 100.0, true));
    }

    public function testSetsStatusToAcceptedAndRecordsTimestampWhenApproved(): void
    {
        $transaction = $this->createMock(Transaction::class);

        $this->repository->method('findByTransactionId')->willReturn($transaction);

        $transaction->expects($this->once())->method('setStatus')->with(TransactionStatus::Accepted);
        $transaction->expects($this->once())->method('setAcceptedAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)(new PaymentProcessedMessage('tx-id', 'corr-id', 100.0, false));
    }

    public function testSkipsUnknownTransaction(): void
    {
        $this->repository->method('findByTransactionId')->willReturn(null);

        $this->entityManager->expects($this->never())->method('flush');

        ($this->handler)(new PaymentProcessedMessage('unknown-id', 'corr-id', 50.0, false));
    }
}
