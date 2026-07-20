<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_auth_001_registration_rejects_numeric_only_names(): void
    {
        $this->postJson('/api/register', $this->registrationPayload([
            'name' => '1234567890',
            'email' => 'numeric-name@example.test',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_api_auth_002_registration_rejects_special_character_only_names(): void
    {
        $this->postJson('/api/register', $this->registrationPayload([
            'name' => '!@#$%^&*()_',
            'email' => 'symbol-name@example.test',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_accepts_arabic_and_latin_names_with_common_separators(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->registrationPayload([
            'name' => 'محمد عبد الله',
            'email' => 'arabic-name@example.test',
        ]))->assertCreated();

        $this->postJson('/api/register', $this->registrationPayload([
            'name' => "Jean-Luc O'Neil",
            'email' => 'latin-name@example.test',
        ]))->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'arabic-name@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'latin-name@example.test']);
    }

    public function test_api_auth_003_registration_rejects_an_invalid_email_address(): void
    {
        $this->postJson('/api/register', $this->registrationPayload([
            'email' => 'not-an-email-address',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Valid User Name',
            'email' => 'valid-user@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ], $overrides);
    }
}
