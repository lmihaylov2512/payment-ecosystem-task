<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Message\InitiatePaymentMessage;
use App\Message\PaymentProcessedMessage;
use App\Serializer\PaymentMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class PaymentMessageSerializerTest extends TestCase
{
    private PaymentMessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new PaymentMessageSerializer();
    }

    public function testEncodeProducesCorrectJsonEnvelope(): void
    {
        $message = new InitiatePaymentMessage('tx-uuid', 'corr-uuid', 99.99, 'EUR');
        $encoded = $this->serializer->encode(new Envelope($message));

        $body = json_decode($encoded['body'], true);

        $this->assertSame('tx-uuid', $body['transaction_id']);
        $this->assertSame('corr-uuid', $body['correlation_id']);
        $this->assertSame(99.99, $body['amount']);
        $this->assertSame('EUR', $body['currency']);
        $this->assertSame('application/json', $encoded['headers']['Content-Type']);
    }

    public function testEncodeAmountIsFloat(): void
    {
        $message = new InitiatePaymentMessage('tx', 'corr', 100.50, 'USD');
        $encoded = $this->serializer->encode(new Envelope($message));

        $body = json_decode($encoded['body'], true);

        $this->assertIsFloat($body['amount']);
        $this->assertSame(100.50, $body['amount']);
    }

    public function testDecodeProducesPaymentProcessedMessage(): void
    {
        $body = json_encode([
            'transaction_id' => 'tx-uuid',
            'correlation_id' => 'corr-uuid',
            'amount'         => 250.0,
            'high_risk'      => true,
        ]);

        $envelope = $this->serializer->decode(['body' => $body, 'headers' => []]);
        /** @var PaymentProcessedMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(PaymentProcessedMessage::class, $message);
        $this->assertSame('tx-uuid', $message->transactionId);
        $this->assertSame('corr-uuid', $message->correlationId);
        $this->assertSame(250.0, $message->amount);
        $this->assertTrue($message->highRisk);
    }

    public function testDecodeHighRiskFalse(): void
    {
        $body = json_encode([
            'transaction_id' => 'tx-uuid',
            'correlation_id' => 'corr-uuid',
            'amount'         => 50.0,
            'high_risk'      => false,
        ]);

        $envelope = $this->serializer->decode(['body' => $body, 'headers' => []]);
        /** @var PaymentProcessedMessage $message */
        $message = $envelope->getMessage();

        $this->assertFalse($message->highRisk);
    }
}
