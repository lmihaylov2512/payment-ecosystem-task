<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\TransactionStatus;
use App\Message\PaymentProcessedMessage;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PaymentProcessedHandler
{
    public function __construct(
        private readonly TransactionRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(PaymentProcessedMessage $message): void
    {
        $transaction = $this->repository->findByTransactionId($message->transactionId);

        if ($transaction === null) {
            return;
        }

        if ($message->highRisk) {
            $transaction->setStatus(TransactionStatus::Flagged);
        } else {
            $transaction->setStatus(TransactionStatus::Accepted);
            $transaction->setAcceptedAt(new DateTimeImmutable());
        }

        $this->entityManager->flush();
    }
}
