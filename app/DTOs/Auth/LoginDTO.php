<?php

namespace App\DTOs\Auth;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'],
            password: $validated['password'],
        );
    }
}
