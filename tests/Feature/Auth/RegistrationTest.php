<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_name' => 'Test Barber Shop',
        ]);

        $this->assertAuthenticated();

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('tenant.name', 'Test Barber Shop')
            ->assertJsonStructure(['tenant' => ['slug', 'schema_name']]);

        $this->assertDatabaseHas((new User)->getTable(), [
            'email' => 'test@example.com',
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas((new Tenant)->getTable(), [
            'name' => 'Test Barber Shop',
        ]);
    }
}
