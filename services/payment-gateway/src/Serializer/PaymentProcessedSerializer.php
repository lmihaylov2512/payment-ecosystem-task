<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\PaymentProcessedMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class PaymentProcessedSerializer implements SerializerInterface
{
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

    public function encode(Envelope $envelope): array
    {
        throw new \LogicException('payment_processed transport is consume-only.');
    }
}
