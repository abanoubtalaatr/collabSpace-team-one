<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_flow_resets_password_with_reset_token_from_verify_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.flow@example.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'otp' => '123456',
            'purpose' => 'password_reset',
        ]);

        $verifyResponse
            ->assertOk()
            ->assertJsonPath('data.reset_token', fn ($token) => is_string($token) && strlen($token) === 40);

        $resetToken = $verifyResponse->json('data.reset_token');

        $resetResponse = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $resetResponse
            ->assertOk()
            ->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_reset_password_fails_when_using_otp_instead_of_reset_token(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong-token@example.test',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => '123456',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reset_token']);
    }
}
