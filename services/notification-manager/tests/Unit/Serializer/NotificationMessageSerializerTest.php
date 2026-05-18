<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Message\PaymentNotificationMessage;
use App\Serializer\NotificationMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class NotificationMessageSerializerTest extends TestCase
{
    private NotificationMessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new NotificationMessageSerializer();
    }

    public function testDecodeMapsAllFields(): void
    {
        $body = json_encode([
            'transaction_id'  => 'tx-uuid',
            'correlation_id'  => 'corr-uuid',
            'amount'          => 199.99,
            'status'          => 'accepted',
            'recipient_email' => 'user@example.com',
        ]);

        $envelope = $this->serializer->decode(['body' => $body, 'headers' => []]);
        /** @var PaymentNotificationMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(PaymentNotificationMessage::class, $message);
        $this->assertSame('tx-uuid', $message->transactionId);
        $this->assertSame('corr-uuid', $message->correlationId);
        $this->assertSame(199.99, $message->amount);
        $this->assertSame('accepted', $message->status);
        $this->assertSame('user@example.com', $message->recipientEmail);
    }

    public function testDecodeAmountIsCastToFloat(): void
    {
        $body = json_encode([
            'transaction_id'  => 'tx-uuid',
            'correlation_id'  => 'corr-uuid',
            'amount'          => '149.50',
            'status'          => 'accepted',
            'recipient_email' => 'user@example.com',
        ]);

        $envelope = $this->serializer->decode(['body' => $body, 'headers' => []]);
        /** @var PaymentNotificationMessage $message */
        $message = $envelope->getMessage();

        $this->assertIsFloat($message->amount);
        $this->assertSame(149.50, $message->amount);
    }

    public function testEncodeThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->serializer->encode(new Envelope(new PaymentNotificationMessage(
            transactionId:  'tx-uuid',
            correlationId:  'corr-uuid',
            amount:         100.0,
            status:         'accepted',
            recipientEmail: 'user@example.com',
        )));
    }
}