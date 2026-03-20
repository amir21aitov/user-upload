<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user_id', 'resend_in']);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        Mail::assertSent(\App\Mail\OtpMail::class);
    }

    public function test_verified_user_cannot_register_again(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'john@example.com',
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(409);
    }

    public function test_user_can_verify_otp(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'john@example.com',
        ]);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 0, 300);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'john@example.com',
            'code' => 123456,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

        $this->assertNotNull($user->fresh()->verified_at);
    }

    public function test_invalid_otp_is_rejected(): void
    {
        User::factory()->unverified()->create([
            'email' => 'john@example.com',
        ]);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 0, 300);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'john@example.com',
            'code' => 999999,
        ]);

        $response->assertStatus(422);
    }

    public function test_otp_brute_force_is_blocked(): void
    {
        User::factory()->unverified()->create([
            'email' => 'john@example.com',
        ]);

        Cache::put('otp:john@example.com', 123456, 300);
        Cache::put('otp_attempts:john@example.com', 5, 300);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'john@example.com',
            'code' => 123456,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Too many attempts. Request a new code.']);
    }

    public function test_verified_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);
    }

    public function test_unverified_user_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_registration_validates_input(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
