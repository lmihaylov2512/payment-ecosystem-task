<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\MapRequestHeader;
use App\Dto\CreateTransactionDto;
use App\Exception\DuplicateTransactionException;
use App\Exception\TransactionNotFoundException;
use App\Service\{Notification\NotificationService, PaymentService};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly NotificationService $notificationService,
    ) {}

    #[Route('/payments/{transactionId}/confirm', methods: ['POST'])]
    public function confirm(string  $transactionId): JsonResponse
    {
        try {
            $transaction = $this->paymentService->confirmTransaction($transactionId);

            $this->notificationService->dispatchPaymentNotification($transaction, $transaction->getUserEmail());

            return $this->json([
                'transaction_id' => $transaction->getTransactionId(),
                'status' => $transaction->getStatus(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'paymentMethod' => $transaction->getPaymentMethod(),
                'created_at' => $transaction->getCreatedAt(),
                'updated_at' => $transaction->getUpdatedAt(),
            ]);
        } catch (TransactionNotFoundException) {
            return $this->json(['error' => 'Transaction not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/payments', methods: ['POST'])]
    public function create(
        #[MapRequestHeader('Request-Id')] string $requestId,
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )] CreateTransactionDto $dto
    ): JsonResponse
    {
        try {
            $transaction = $this->paymentService->createTransaction($dto, $requestId);

            $this->notificationService->dispatchPaymentInitiation($transaction);

            return $this->json([
                'transaction_id' => $transaction->getTransactionId(),
                'status' => $transaction->getStatus(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'paymentMethod' => $transaction->getPaymentMethod(),
                'created_at' => $transaction->getCreatedAt(),
            ]);
        } catch (DuplicateTransactionException) {
            return $this->json(
                ['error' => 'Duplicate transaction for this idempotency key'],
                Response::HTTP_CONFLICT,
            );
        }
    }
}
