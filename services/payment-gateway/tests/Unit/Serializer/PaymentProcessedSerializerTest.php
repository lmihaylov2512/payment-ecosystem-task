<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Message\PaymentProcessedMessage;
use App\Serializer\PaymentProcessedSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class PaymentProcessedSerializerTest extends TestCase
{
    private PaymentProcessedSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new PaymentProcessedSerializer();
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

    public function testEncodeThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->serializer->encode(new Envelope(new PaymentProcessedMessage('tx', 'corr', 1.0, false)));
    }
}