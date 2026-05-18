<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\{InitiatePaymentMessage, PaymentProcessedMessage};
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Envelope;

class PaymentMessageSerializer implements SerializerInterface
{
    public function encode(Envelope $envelope): array
    {
        /** @var InitiatePaymentMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode([
                'transaction_id' => $message->transactionId,
                'correlation_id' => $message->correlationId,
                'amount' => (float) $message->amount,
                'currency' => $message->currency,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $data = json_decode($encodedEnvelope['body'], true);

        return new Envelope(new PaymentProcessedMessage(
            transactionId: $data['transaction_id'],
            correlationId: $data['correlation_id'],
            amount: (float) $data['amount'],
            highRisk: (bool) $data['high_risk'],
        ));
    }
}
