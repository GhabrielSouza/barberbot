<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\BotService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_dependencies_can_be_loaded(): void
    {
        $this->getJson('/api/webhook/test')->assertOk()->assertJsonPath('success', true);
    }

    public function test_webhook_dispatches_a_message_to_the_bot(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        $company = Company::create(['name' => 'Barbearia']);
        $this->mock(BotService::class, function ($mock) use ($company) {
            $mock->shouldReceive('processMessage')->once()->withArgs(function ($received, $phone, $message) use ($company) {
                return $received->id === $company->id && $phone === '11999999999' && $message === 'Olá';
            });
        });
        $this->postJson('/api/webhook/whatsapp', ['phone' => '11999999999', 'message' => 'Olá'], ['X-Company-ID' => $company->id])
            ->assertOk()->assertJsonPath('success', true);
        $this->postJson('/api/webhook/whatsapp', [], ['X-Company-ID' => $company->id])->assertStatus(400);
    }

    public function test_both_sending_methods_build_the_provider_url(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.z-api.io/*' => Http::response(['error' => 'unavailable'], 503)]);
        $company = new Company(['whatsapp_instance' => 'instance', 'whatsapp_token' => 'token']);
        $user = new User;
        $user->phone = '11999999999';
        $service = app(WhatsAppService::class);
        $this->assertFalse($service->sendMessage($company, $user, 'Olá'));
        $this->assertFalse($service->sendMessageWithButtons($company, $user, 'Escolha', ['Corte']));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.z-api.io/instances/instance/token/token/send-message'
            && $request['phone'] === '11999999999' && ($request->data()['buttons'] ?? null) === ['Corte']);
    }
}
