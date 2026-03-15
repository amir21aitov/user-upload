<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserNotVerifiedException;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class AuthService
{
    public function setOtpCode(RegisterDTO $dto): array
    {
        $user = User::query()->where('email', $dto->email)->first();

        if ($user && $user->verified_at !== null) {
            throw new UserAlreadyExistsException();
        }
        $code = random_int(100000, 999999);
        Redis::client()->set($dto->email, $code, ['ex' => 300]);

        Mail::to($dto->email)->send(new OtpMail($code));

        if (!$user) {
            $user = User::query()->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
            ]);
        } else {
            $user->update([
                'name' => $dto->name,
                'password' => $dto->password,
            ]);
        }

        return [
            'user_id' => $user->id,
            'resend' => 60,
        ];
    }

    public function verifyOtpCode(string $email, int $code): User
    {
        $user = User::query()->where('email', $email)->whereNull('verified_at')->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        $cached = Redis::client()->get($email);

        if (!$cached || (int) $cached !== $code) {
            throw new InvalidOtpException();
        }

        Redis::client()->del($email);

        $user->update(['verified_at' => now()]);

        return $user->refresh();
    }

    public function login(LoginDTO $dto): array
    {
        $user = User::query()->where('email', $dto->email)->first();

        if (!$user) {
            throw new InvalidCredentialsException();
        }

        if ($user->verified_at === null) {
            throw new UserNotVerifiedException();
        }

        $token = auth('api')->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        if (!$token) {
            throw new InvalidCredentialsException();
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
