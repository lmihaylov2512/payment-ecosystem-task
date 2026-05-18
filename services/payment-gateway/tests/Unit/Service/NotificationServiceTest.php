<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Transaction;
use App\Enum\TransactionStatus;
use App\Message\InitiatePaymentMessage;
use App\Message\PaymentNotificationMessage;
use App\Service\Notification\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class NotificationServiceTest extends TestCase
{
    private MessageBusInterface&MockObject $bus;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->bus     = $this->createMock(MessageBusInterface::class);
        $this->service = new NotificationService($this->bus);
    }

    private function makeTransaction(
        string $transactionId = 'aaa00000-0000-7000-8000-000000000001',
        string $correlationId = 'bbb00000-0000-7000-8000-000000000002',
        string $userEmail     = 'user@example.com',
        float  $amount        = 149.99,
        string $currency      = 'EUR',
    ): Transaction {
        return new Transaction(
            transactionId: Uuid::fromString($transactionId),
            userId:        Uuid::v4(),
            userEmail:     $userEmail,
            amount:        $amount,
            currency:      $currency,
            paymentMethod: 'card',
            requestId:     'req-001',
            correlationId: Uuid::fromString($correlationId),
        );
    }

    public function testDispatchPaymentInitiationDispatchesInitiatePaymentMessage(): void
    {
        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(InitiatePaymentMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->dispatchPaymentInitiation($this->makeTransaction());
    }

    public function testDispatchPaymentInitiationMapsFieldsCorrectly(): void
    {
        $transaction = $this->makeTransaction(
            transactionId: 'aaa00000-0000-7000-8000-000000000001',
            correlationId: 'bbb00000-0000-7000-8000-000000000002',
            amount:        99.50,
            currency:      'USD',
        );

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (InitiatePaymentMessage $msg): bool {
                return $msg->transactionId === 'aaa00000-0000-7000-8000-000000000001'
                    && $msg->correlationId === 'bbb00000-0000-7000-8000-000000000002'
                    && $msg->amount        === 99.50
                    && $msg->currency      === 'USD';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->dispatchPaymentInitiation($transaction);
    }

    public function testDispatchPaymentNotificationDispatchesPaymentNotificationMessage(): void
    {
        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PaymentNotificationMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->dispatchPaymentNotification($this->makeTransaction(), 'user@example.com');
    }

    public function testDispatchPaymentNotificationUsesProvidedRecipientEmail(): void
    {
        $transaction = $this->makeTransaction(
            transactionId: 'aaa00000-0000-7000-8000-000000000001',
            correlationId: 'bbb00000-0000-7000-8000-000000000002',
            amount:        200.0,
        );
        $transaction->setStatus(TransactionStatus::Accepted);

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PaymentNotificationMessage $msg): bool {
                return $msg->transactionId  === 'aaa00000-0000-7000-8000-000000000001'
                    && $msg->correlationId  === 'bbb00000-0000-7000-8000-000000000002'
                    && $msg->recipientEmail === 'custom@example.com'
                    && $msg->amount         === 200.0
                    && $msg->status         === 'accepted';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->dispatchPaymentNotification($transaction, 'custom@example.com');
    }

    public function testDispatchPaymentNotificationReflectsCurrentTransactionStatus(): void
    {
        $transaction = $this->makeTransaction();
        $transaction->setStatus(TransactionStatus::Flagged);

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PaymentNotificationMessage $msg): bool {
                return $msg->status === 'flagged';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->dispatchPaymentNotification($transaction, 'admin@example.com');
    }
}