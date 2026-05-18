<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler;

use App\Message\PaymentNotificationMessage;
use App\MessageHandler\PaymentNotificationHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentNotificationHandlerIntegrationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
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

    private function handlerWithMailer(MailerInterface $mailer): PaymentNotificationHandler
    {
        static::getContainer()->set(MailerInterface::class, $mailer);

        return static::getContainer()->get(PaymentNotificationHandler::class);
    }

    public function testHandlerIsResolvedFromContainer(): void
    {
        $handler = static::getContainer()->get(PaymentNotificationHandler::class);

        $this->assertInstanceOf(PaymentNotificationHandler::class, $handler);
    }

    public function testHandlerDispatchesEmailViaMailer(): void
    {
        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        ($this->handlerWithMailer($mailer))($this->makeMessage());
    }

    public function testEmailRecipientMatchesMessage(): void
    {
        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getTo()[0]->getAddress() === 'admin@example.com';
            }));

        ($this->handlerWithMailer($mailer))($this->makeMessage(recipientEmail: 'admin@example.com'));
    }

    public function testEmailSubjectReflectsStatusAndTransactionId(): void
    {
        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return str_contains($email->getSubject(), 'Flagged')
                    && str_contains($email->getSubject(), 'tx-integration');
            }));

        ($this->handlerWithMailer($mailer))($this->makeMessage(transactionId: 'tx-integration', status: 'flagged'));
    }
}