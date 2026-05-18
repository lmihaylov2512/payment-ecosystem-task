<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Message\InitiatePaymentMessage;
use App\Serializer\PaymentInitiatedSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class PaymentInitiatedSerializerTest extends TestCase
{
    private PaymentInitiatedSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new PaymentInitiatedSerializer();
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

    public function testDecodeThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->serializer->decode(['body' => '{}', 'headers' => []]);
    }
}