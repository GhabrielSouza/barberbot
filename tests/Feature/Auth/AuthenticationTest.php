<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('tenant.id', $user->tenant_id);
    }

    public function test_passwords_are_stored_using_argon2id(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->assertStringStartsWith('$argon2id$', $user->password_hash);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertNoContent();
    }

    public function test_authenticated_users_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user');

        $response
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('tenant.id', $user->tenant_id);
    }
}
