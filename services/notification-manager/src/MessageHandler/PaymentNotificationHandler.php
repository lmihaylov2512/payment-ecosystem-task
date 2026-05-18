<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PaymentNotificationMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class PaymentNotificationHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public function __invoke(PaymentNotificationMessage $message): void
    {
        $email = (new Email())
            ->from('noreply@payment-ecosystem.local')
            ->to($message->recipientEmail)
            ->subject(sprintf('Payment %s – transaction %s', ucfirst($message->status), $message->transactionId))
            ->html($this->buildBody($message));

        $this->mailer->send($email);
    }

    private function buildBody(PaymentNotificationMessage $message): string
    {
        return sprintf(
            '<p>Your payment of <strong>%.2f</strong> has been <strong>%s</strong>.</p>
<p>Transaction ID: %s</p>',
            $message->amount,
            $message->status,
            $message->transactionId,
        );
    }
}
