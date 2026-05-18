<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Message\PaymentNotificationMessage;
use App\Serializer\PaymentNotificationSerializer;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class PaymentNotificationSerializerTest extends TestCase
{
    private PaymentNotificationSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new PaymentNotificationSerializer();
    }

    public function testEncodeProducesCorrectJsonEnvelope(): void
    {
        $message = new PaymentNotificationMessage(
            transactionId:  'tx-uuid',
            correlationId:  'corr-uuid',
            amount:         149.99,
            status:         'accepted',
            recipientEmail: 'user@example.com',
        );

        $encoded = $this->serializer->encode(new Envelope($message));
        $body    = json_decode($encoded['body'], true);

        $this->assertSame('tx-uuid', $body['transaction_id']);
        $this->assertSame('corr-uuid', $body['correlation_id']);
        $this->assertSame(149.99, $body['amount']);
        $this->assertSame('accepted', $body['status']);
        $this->assertSame('user@example.com', $body['recipient_email']);
        $this->assertSame('application/json', $encoded['headers']['Content-Type']);
    }

    public function testEncodeUsesRecipientEmailField(): void
    {
        $message = new PaymentNotificationMessage(
            transactionId:  'tx-uuid',
            correlationId:  'corr-uuid',
            amount:         100.0,
            status:         'flagged',
            recipientEmail: 'admin@payment-ecosystem.local',
        );

        $encoded = $this->serializer->encode(new Envelope($message));
        $body    = json_decode($encoded['body'], true);

        $this->assertArrayHasKey('recipient_email', $body);
        $this->assertArrayNotHasKey('user_email', $body);
        $this->assertSame('admin@payment-ecosystem.local', $body['recipient_email']);
    }

    public function testDecodeThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);

        $this->serializer->decode(['body' => '{}', 'headers' => []]);
    }
}