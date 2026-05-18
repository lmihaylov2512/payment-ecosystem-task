<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Dto\CreateTransactionDto;
use App\Enum\TransactionStatus;
use App\Exception\DuplicateTransactionException;
use App\Exception\TransactionNotFoundException;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentServiceTest extends KernelTestCase
{
    private PaymentService $paymentService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);

        $this->entityManager  = static::getContainer()->get(EntityManagerInterface::class);
        $this->paymentService = static::getContainer()->get(PaymentService::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata   = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function makeDto(string $userId = '01960000-0000-7000-8000-000000000001'): CreateTransactionDto
    {
        return new CreateTransactionDto(
            userId:        $userId,
            userEmail:     'user@example.com',
            amount:        150.00,
            currency:      'EUR',
            paymentMethod: 'card',
        );
    }

    public function testCreateTransactionPersistsToDatabase(): void
    {
        $transaction = $this->paymentService->createTransaction($this->makeDto(), 'req-id-1');

        $this->assertNotNull($transaction->getId());
        $this->assertNotNull($transaction->getTransactionId());
        $this->assertNotNull($transaction->getCorrelationId());
        $this->assertSame(TransactionStatus::Pending, $transaction->getStatus());
        $this->assertSame('user@example.com', $transaction->getUserEmail());
        $this->assertSame('EUR', $transaction->getCurrency());
        $this->assertSame(150.00, $transaction->getAmount());
    }

    public function testCreateTransactionThrowsOnDuplicateRequestId(): void
    {
        $this->paymentService->createTransaction($this->makeDto(), 'req-id-dup');

        $this->expectException(DuplicateTransactionException::class);

        $this->paymentService->createTransaction($this->makeDto(), 'req-id-dup');
    }

    public function testRequestIdIsGloballyUnique(): void
    {
        $this->paymentService->createTransaction(
            $this->makeDto('01960000-0000-7000-8000-000000000001'),
            'shared-req-id',
        );

        $this->expectException(DuplicateTransactionException::class);

        $this->paymentService->createTransaction(
            $this->makeDto('01960000-0000-7000-8000-000000000002'),
            'shared-req-id',
        );
    }

    public function testTransactionIdsAreUnique(): void
    {
        $first  = $this->paymentService->createTransaction($this->makeDto(), 'req-id-a');
        $second = $this->paymentService->createTransaction($this->makeDto(), 'req-id-b');

        $this->assertNotSame(
            (string) $first->getTransactionId(),
            (string) $second->getTransactionId(),
        );
    }

    public function testConfirmTransactionSetsAcceptedStatus(): void
    {
        $created  = $this->paymentService->createTransaction($this->makeDto(), 'req-id-confirm');
        $confirmed = $this->paymentService->confirmTransaction((string) $created->getTransactionId());

        $this->assertSame(TransactionStatus::Accepted, $confirmed->getStatus());
        $this->assertNotNull($confirmed->getAcceptedAt());
    }

    public function testConfirmTransactionThrowsOnUnknownTransactionId(): void
    {
        $this->expectException(TransactionNotFoundException::class);

        $this->paymentService->confirmTransaction('00000000-0000-7000-8000-000000000000');
    }
}
