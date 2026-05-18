<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Message\PaymentNotificationMessage;
use App\MessageHandler\PaymentNotificationHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentNotificationHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private PaymentNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->mailer  = $this->createMock(MailerInterface::class);
        $this->handler = new PaymentNotificationHandler($this->mailer);
    }

    private function makeMessage(
        string $transactionId  = 'tx-uuid',
        string $correlationId  = 'corr-uuid',
        float  $amount         = 149.99,
        string $status         = 'accepted',
        string $recipientEmail = 'user@example.com',
    ): PaymentNotificationMessage {
        return new PaymentNotificationMessage(
            transactionId:  $transactionId,
            correlationId:  $correlationId,
            amount:         $amount,
            status:         $status,
            recipientEmail: $recipientEmail,
        );
    }

    public function testSendsOneEmail(): void
    {
        $this->mailer->expects($this->once())->method('send');

        ($this->handler)($this->makeMessage());
    }

    public function testEmailIsAddressedToRecipient(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getTo()[0]->getAddress() === 'recipient@example.com';
            }));

        ($this->handler)($this->makeMessage(recipientEmail: 'recipient@example.com'));
    }

    public function testEmailSubjectContainsStatusAndTransactionId(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $subject = $email->getSubject();
                return str_contains($subject, 'Accepted')
                    && str_contains($subject, 'tx-abc');
            }));

        ($this->handler)($this->makeMessage(transactionId: 'tx-abc', status: 'accepted'));
    }

    public function testEmailBodyContainsAmountStatusAndTransactionId(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $body = $email->getHtmlBody();
                return str_contains($body, '250.00')
                    && str_contains($body, 'flagged')
                    && str_contains($body, 'tx-xyz');
            }));

        ($this->handler)($this->makeMessage(
            transactionId: 'tx-xyz',
            amount:        250.0,
            status:        'flagged',
        ));
    }

    public function testEmailIsFromNoreply(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getFrom()[0]->getAddress() === 'noreply@payment-ecosystem.local';
            }));

        ($this->handler)($this->makeMessage());
    }
}