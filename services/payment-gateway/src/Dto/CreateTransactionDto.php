<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateTransactionDto
{
    public function __construct(
        #[SerializedName('user_id')]
        #[Assert\NotBlank(message: 'user_id is required.')]
        #[Assert\Uuid(message: 'user_id must be a valid UUID.')]
        public string $userId,

        #[SerializedName('user_email')]
        #[Assert\NotBlank(message: 'user_email is required.')]
        #[Assert\Email(message: 'user_email must be a valid email address.')]
        public string $userEmail,

        #[Assert\NotBlank(message: 'amount is required.')]
        #[Assert\Positive(message: 'amount must be a positive number.')]
        public float $amount,

        #[Assert\NotBlank(message: 'currency is required.')]
        #[Assert\Length(exactly: 3, exactMessage: 'currency must be exactly 3 characters (e.g. EUR, USD).')]
        public string $currency,

        #[SerializedName('payment_method')]
        #[Assert\NotBlank(message: 'payment_method is required.')]
        public string $paymentMethod,
    ) {}
}
