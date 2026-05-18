<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\PaymentNotificationMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use LogicException;

class PaymentNotificationSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        throw new LogicException('payment_notification transport is publish-only.');
    }

    public function encode(Envelope $envelope): array
    {
        /** @var PaymentNotificationMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode([
                'transaction_id' => $message->transactionId,
                'correlation_id' => $message->correlationId,
                'amount' => $message->amount,
                'status' => $message->status,
                'recipient_email' => $message->recipientEmail,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
