<?php

namespace App\Contracts;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\LoginResultDTO;
use App\DTOs\Auth\OtpResultDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Models\User;

interface AuthServiceInterface
{
    public function register(RegisterDTO $dto): OtpResultDTO;

    public function verifyOtp(string $email, int $code): User;

    public function login(LoginDTO $dto): LoginResultDTO;
}
