<?php

namespace Tests\Unit\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Events\OtpVerified;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserNotVerifiedException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuthService::class);
    }

    // --- register ---

    public function test_register_creates_user_and_sends_otp(): void
    {
        Mail::fake();
        Event::fake([UserRegistered::class]);

        $dto = new RegisterDTO('John', 'john@example.com', 'password123');

        $result = $this->service->register($dto);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertNotNull(Cache::get('otp:john@example.com'));
        $this->assertEquals(60, $result->resendIn);

        Mail::assertSent(\App\Mail\OtpMail::class);
        Event::assertDispatched(UserRegistered::class, fn ($e) => $e->user->email === 'john@example.com');
    }

    public function test_register_updates_unverified_user(): void
    {
        Mail::fake();
        Event::fake([UserRegistered::class]);

        User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'name' => 'Old Name',
        ]);

        $dto = new RegisterDTO('New Name', 'john@example.com', 'newpassword');

        $this->service->register($dto);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'New Name',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_throws_for_verified_user(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'john@example.com',
            'verified_at' => now(),
        ]);

        $this->expectException(UserAlreadyExistsException::class);

        $this->service->register(new RegisterDTO('John', 'john@example.com', 'password'));
    }

    // --- verifyOtp ---

    public function test_verify_otp_with_valid_code(): void
    {
        Event::fake([OtpVerified::class]);

        $user = User::factory()->unverified()->create(['email' => 'john@example.com']);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 0, 300);

        $verified = $this->service->verifyOtp('john@example.com', 123456);

        $this->assertNotNull($verified->verified_at);
        $this->assertNull(Cache::get('otp:john@example.com'));
        Event::assertDispatched(OtpVerified::class, fn ($e) => $e->user->id === $user->id);
    }

    public function test_verify_otp_throws_for_invalid_code(): void
    {
        User::factory()->unverified()->create(['email' => 'john@example.com']);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 0, 300);

        $this->expectException(InvalidOtpException::class);

        $this->service->verifyOtp('john@example.com', 999999);
    }

    public function test_verify_otp_increments_attempts_on_failure(): void
    {
        User::factory()->unverified()->create(['email' => 'john@example.com']);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 2, 300);

        try {
            $this->service->verifyOtp('john@example.com', 999999);
        } catch (InvalidOtpException) {
        }

        $this->assertEquals(3, Cache::get('otp_attempts:john@example.com'));
    }

    public function test_verify_otp_blocks_after_max_attempts(): void
    {
        User::factory()->unverified()->create(['email' => 'john@example.com']);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 5, 300);

        try {
            $this->service->verifyOtp('john@example.com', 123456);
            $this->fail('Expected InvalidOtpException');
        } catch (InvalidOtpException $e) {
            $this->assertStringContainsString('Too many attempts', $e->getMessage());
        }

        $this->assertNull(Cache::get('otp:john@example.com'));
    }

    public function test_verify_otp_throws_for_unknown_email(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->service->verifyOtp('nobody@example.com', 123456);
    }

    // --- login ---

    public function test_login_returns_token_for_verified_user(): void
    {
        Event::fake([UserLoggedIn::class]);

        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
            'verified_at' => now(),
        ]);

        $result = $this->service->login(new LoginDTO('john@example.com', 'password123'));

        $this->assertNotEmpty($result->token);
        $this->assertEquals('john@example.com', $result->user->email);
        Event::assertDispatched(UserLoggedIn::class);
    }

    public function test_login_throws_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
            'verified_at' => now(),
        ]);

        $this->expectException(InvalidCredentialsException::class);

        $this->service->login(new LoginDTO('john@example.com', 'wrong'));
    }

    public function test_login_throws_for_unverified_user(): void
    {
        User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->expectException(UserNotVerifiedException::class);

        $this->service->login(new LoginDTO('john@example.com', 'password123'));
    }

    public function test_login_throws_for_nonexistent_user(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->service->login(new LoginDTO('nobody@example.com', 'password'));
    }
}
