<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\PaymentNotificationMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class NotificationMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $data = json_decode($encodedEnvelope['body'], true);

        return new Envelope(new PaymentNotificationMessage(
            transactionId: $data['transaction_id'],
            correlationId: $data['correlation_id'],
            amount: (float) $data['amount'],
            status: $data['status'],
            recipientEmail: $data['recipient_email'],
        ));
    }

    public function encode(Envelope $envelope): array
    {
        throw new \LogicException('payment_notification transport is consume-only.');
    }
}
