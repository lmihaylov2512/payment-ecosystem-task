<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TransactionStatus;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $transactionId;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $userId;

    #[ORM\Column(length: 255)]
    private string $userEmail;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(length: 50)]
    private string $paymentMethod;

    #[ORM\Column(length: 50, enumType: TransactionStatus::class)]
    private TransactionStatus $status;

    #[ORM\Column(length: 255, unique: true)]
    private string $requestId;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $correlationId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $acceptedAt = null;

    public function __construct(
        Uuid $transactionId,
        Uuid $userId,
        string $userEmail,
        float $amount,
        string $currency,
        string $paymentMethod,
        string $requestId,
        Uuid $correlationId,
    ) {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
        $this->status = TransactionStatus::Pending;
        $this->requestId = $requestId;
        $this->correlationId = $correlationId;
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getTransactionId(): Uuid { return $this->transactionId; }
    public function getUserId(): Uuid { return $this->userId; }
    public function getUserEmail(): string { return $this->userEmail; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getStatus(): TransactionStatus { return $this->status; }
    public function getRequestId(): string { return $this->requestId; }
    public function getCorrelationId(): Uuid { return $this->correlationId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
    public function getAcceptedAt(): ?DateTimeImmutable { return $this->acceptedAt; }

    public function setStatus(TransactionStatus $status): void
    {
        $this->status = $status;
    }

    public function setAcceptedAt(DateTimeImmutable $acceptedAt): void
    {
        $this->acceptedAt = $acceptedAt;
    }
}
