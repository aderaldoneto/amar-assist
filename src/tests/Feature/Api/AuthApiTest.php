<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/clients')
            ->assertUnauthorized();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@amarassist.test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@amarassist.test',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath(
                'user.email',
                'admin@amarassist.test'
            )
            ->assertJsonStructure([
                'token',
                'token_type',
                'user',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'amar-assist-web',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@amarassist.test',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@amarassist.test',
            'password' => 'incorrect-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Invalid credentials.'
            );
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user
            ->createToken('amar-assist-web')
            ->plainTextToken;

        $this->withHeader(
            'Authorization',
            "Bearer {$token}"
        )
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'amar-assist-web',
        ]);
    }
}
