<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\TransactionStatus;
use App\Message\PaymentProcessedMessage;
use App\Service\Notification\RecipientManager;
use App\Repository\TransactionRepository;
use App\Service\Notification\NotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class PaymentProcessedHandler
{
    public function __construct(
        private TransactionRepository  $repository,
        private EntityManagerInterface $entityManager,
        private NotificationService    $notificationService,
        private RecipientManager       $recipientManager,
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

        $recipient = $this->recipientManager->resolve($message->highRisk);
        $this->notificationService->dispatchPaymentNotification($transaction, $recipient->resolveEmail($transaction));
    }
}
