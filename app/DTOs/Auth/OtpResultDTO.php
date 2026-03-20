<?php

namespace App\DTOs\Auth;

final readonly class OtpResultDTO
{
    public function __construct(
        public int $userId,
        public int $resendIn,
    ) {}
}
