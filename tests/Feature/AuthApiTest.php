<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_working_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $token = $this->postJson('/api/login', ['email' => 'test@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password')
            ->json('token');

        $this->withToken($token)->postJson('/api/counties', ['name' => 'Teszt megye'])->assertCreated();
    }

    public function test_login_rejects_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $this->postJson('/api/login', ['email' => 'test@example.com', 'password' => 'rossz'])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => 'nincs@example.com', 'password' => 'password'])->assertUnauthorized();
        $this->postJson('/api/login', [])->assertUnprocessable();
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);
        $token = $this->postJson('/api/login', ['email' => 'test@example.com', 'password' => 'password'])->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
