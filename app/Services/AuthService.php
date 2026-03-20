<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\LoginResultDTO;
use App\DTOs\Auth\OtpResultDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserNotVerifiedException;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    private const OTP_TTL_SECONDS = 300;
    private const OTP_RESEND_SECONDS = 60;
    private const OTP_MAX_ATTEMPTS = 5;

    public function register(RegisterDTO $dto): OtpResultDTO
    {
        $user = User::where('email', $dto->email)->first();

        if ($user?->verified_at !== null) {
            throw new UserAlreadyExistsException();
        }

        $code = random_int(100000, 999999);

        $user = DB::transaction(function () use ($user, $dto, $code) {
            if ($user) {
                $user->update([
                    'name' => $dto->name,
                    'password' => $dto->password,
                ]);
            } else {
                $user = User::create([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'password' => $dto->password,
                ]);
            }

            Cache::put("otp:{$dto->email}", $code, self::OTP_TTL_SECONDS);
            Cache::put("otp_attempts:{$dto->email}", 0, self::OTP_TTL_SECONDS);

            return $user;
        });

        Mail::to($dto->email)->send(new OtpMail($code));

        return new OtpResultDTO(
            userId: $user->id,
            resendIn: self::OTP_RESEND_SECONDS,
        );
    }

    public function verifyOtp(string $email, int $code): User
    {
        $user = User::where('email', $email)->whereNull('verified_at')->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        $attemptsKey = "otp_attempts:{$email}";
        $attempts = (int) Cache::get($attemptsKey, 0);

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget("otp:{$email}");
            Cache::forget($attemptsKey);
            throw new InvalidOtpException('Too many attempts. Request a new code.');
        }

        $cached = Cache::get("otp:{$email}");

        if (!$cached || (int) $cached !== $code) {
            Cache::increment($attemptsKey);
            throw new InvalidOtpException();
        }

        Cache::forget("otp:{$email}");
        Cache::forget($attemptsKey);

        $user->update(['verified_at' => now()]);

        return $user->refresh();
    }

    public function login(LoginDTO $dto): LoginResultDTO
    {
        $user = User::where('email', $dto->email)->first();

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

        return new LoginResultDTO(
            user: $user,
            token: $token,
        );
    }
}
