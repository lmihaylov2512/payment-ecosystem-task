<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\InitiatePaymentMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class PaymentInitiatedSerializer implements SerializerInterface
{
    public function encode(Envelope $envelope): array
    {
        /** @var InitiatePaymentMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode([
                'transaction_id' => $message->transactionId,
                'correlation_id' => $message->correlationId,
                'amount' => $message->amount,
                'currency' => $message->currency,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        throw new \LogicException('payment_initiated transport is publish-only.');
    }
}
