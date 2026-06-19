<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_returns_json_not_found(): void
    {
        $this->getJson('/')
            ->assertNotFound()
            ->assertJsonPath('message', 'The route / could not be found.');
    }

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'juan@example.com')
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
    }

    public function test_a_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'juan@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_invalid_login_returns_unauthorized(): void
    {
        User::factory()->create(['email' => 'juan@example.com']);

        $this->postJson('/api/login', [
            'email' => 'juan@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_protected_endpoints_require_a_token(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
        $this->putJson('/api/change-password')->assertUnauthorized();
    }

    public function test_an_authenticated_user_can_view_users_and_their_profile(): void
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'users');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password');
    }

    public function test_an_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)->putJson('/api/change-password', [
            'current_password' => 'old-password',
            'new_password' => 'new-password123',
            'new_password_confirmation' => 'new-password123',
        ])->assertOk()->assertJsonPath('message', 'Password changed successfully.');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)->putJson('/api/change-password', [
            'current_password' => 'incorrect-password',
            'new_password' => 'new-password123',
            'new_password_confirmation' => 'new-password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }
}
