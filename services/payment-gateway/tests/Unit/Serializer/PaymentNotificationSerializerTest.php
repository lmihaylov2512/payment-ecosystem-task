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
            transactionId: 'tx-uuid',
            correlationId: 'corr-uuid',
            userEmail:     'user@example.com',
            amount:        149.99,
            status:        'accepted',
        );

        $encoded = $this->serializer->encode(new Envelope($message));
        $body    = json_decode($encoded['body'], true);

        $this->assertSame('tx-uuid', $body['transaction_id']);
        $this->assertSame('corr-uuid', $body['correlation_id']);
        $this->assertSame('user@example.com', $body['user_email']);
        $this->assertSame(149.99, $body['amount']);
        $this->assertSame('accepted', $body['status']);
        $this->assertSame('application/json', $encoded['headers']['Content-Type']);
    }

    public function testDecodeThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);

        $this->serializer->decode(['body' => '{}', 'headers' => []]);
    }
}
