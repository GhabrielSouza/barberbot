<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ApiSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        // Exercise CSRF in tests instead of Laravel's automatic test bypass.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_login_requires_csrf_with_and_without_origin(): void
    {
        $this->postJson('/api/login', [])->assertStatus(419);
        $this->withHeader('Origin', 'http://localhost')->postJson('/api/login', [])->assertStatus(419);
    }

    public function test_encrypted_session_survives_origin_changes_and_logout(): void
    {
        $user = User::factory()->create();
        $csrf = $this->get('/sanctum/csrf-cookie')->assertNoContent();
        $cookies = collect($csrf->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());
        $sessionName = config('session.cookie');
        $this->withUnencryptedCookies([
            $sessionName => $cookies[$sessionName]->getValue(),
            'XSRF-TOKEN' => $cookies['XSRF-TOKEN']->getValue(),
        ]);
        $login = $this->withHeader('X-XSRF-TOKEN', $cookies['XSRF-TOKEN']->getValue())
            ->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $cookies = collect($login->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());
        $this->withUnencryptedCookies([
            $sessionName => $cookies[$sessionName]->getValue(),
            'XSRF-TOKEN' => $cookies['XSRF-TOKEN']->getValue(),
        ]);
        Auth::forgetGuards();
        $this->withHeader('Origin', 'http://localhost')->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
        $this->withHeader('X-XSRF-TOKEN', $cookies['XSRF-TOKEN']->getValue())->postJson('/api/logout')->assertNoContent();
        Auth::forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
