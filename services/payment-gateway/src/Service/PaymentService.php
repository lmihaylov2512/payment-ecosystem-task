<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateTransactionDto;
use App\Entity\Transaction;
use App\Enum\TransactionStatus;
use App\Exception\DuplicateTransactionException;
use App\Exception\TransactionNotFoundException;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionRepository $repository,
    ) {}

    public function createTransaction(CreateTransactionDto $dto, string $requestId): Transaction
    {
        if ($this->repository->findByRequestId($requestId) !== null) {
            throw new DuplicateTransactionException();
        }

        $transaction = new Transaction(
            transactionId: Uuid::v7(),
            userId: Uuid::fromString($dto->userId),
            userEmail: $dto->userEmail,
            amount: $dto->amount,
            currency: strtoupper($dto->currency),
            paymentMethod: $dto->paymentMethod,
            requestId: $requestId,
            correlationId: Uuid::v7(),
        );

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    public function confirmTransaction(string $transactionId): Transaction
    {
        $transaction = $this->repository->findByTransactionId($transactionId);

        if ($transaction === null) {
            throw new TransactionNotFoundException();
        }

        $transaction->setStatus(TransactionStatus::Accepted);
        $transaction->setAcceptedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $transaction;
    }
}
