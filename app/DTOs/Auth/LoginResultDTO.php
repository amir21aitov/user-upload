<?php

namespace App\DTOs\Auth;

use App\Models\User;

final readonly class LoginResultDTO
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
