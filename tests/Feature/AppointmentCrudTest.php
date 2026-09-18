<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantDatabase;

    private array $payload;

    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantDatabase = tempnam(sys_get_temp_dir(), 'barber-tenant-');
        config(['database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->tenantDatabase, 'prefix' => '', 'foreign_key_constraints' => false]]);
        $schema = Schema::connection('tenant');
        $schema->create('clients', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
        });
        $schema->create('team_members', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->boolean('active');
        });
        $schema->create('services', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->boolean('active');
            $table->decimal('price');
        });
        $schema->create('appointments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('client_id');
            $table->string('team_member_id');
            $table->string('service_id');
            $table->string('service_name');
            $table->decimal('price');
            $table->date('date');
            $table->time('start_time');
            $table->string('status');
        });
        $db = DB::connection('tenant');
        $db->table('clients')->insert(['id' => 'client', 'name' => 'Cliente']);
        $db->table('team_members')->insert(['id' => 'barber', 'name' => 'Barbeiro', 'active' => true]);
        $db->table('services')->insert(['id' => 'service', 'name' => 'Corte', 'active' => true, 'price' => 999]);
        foreach (['done', 'pending', 'confirmed', 'cancelled'] as $status) {
            $db->table('appointments')->insert([
                'id' => $status, 'client_id' => 'client', 'team_member_id' => 'barber', 'service_id' => 'service',
                'service_name' => 'Corte', 'price' => 50, 'date' => today()->toDateString(), 'start_time' => '10:00:00', 'status' => $status,
            ]);
        }
        Schema::connection('tenant')->table('appointments', function (Blueprint $table) {
            $table->time('end_time')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::connection('tenant')->table('services', function (Blueprint $table) {
            $table->integer('duration_min')->default(45);
        });
        $db = DB::connection('tenant');
        $db->table('appointments')->delete();
        $client = (string) Str::uuid();
        $barber = (string) Str::uuid();
        $service = (string) Str::uuid();
        $db->table('clients')->insert(['id' => $client, 'name' => 'Cliente']);
        $db->table('team_members')->insert(['id' => $barber, 'name' => 'Barbeiro', 'active' => true]);
        $db->table('services')->insert(['id' => $service, 'name' => 'Corte', 'active' => true, 'price' => 50, 'duration_min' => 45]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->base = '/api/companies/'.$user->tenant_id.'/appointments';
        $this->payload = ['client_id' => $client, 'team_member_id' => $barber, 'service_id' => $service,
            'date' => today()->toDateString(), 'start_time' => '10:00'];
    }

    public function test_appointment_crud_uses_the_tenant_schema(): void
    {
        $response = $this->postJson($this->base, $this->payload)->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.end_time', '10:45:00');
        $id = $response->json('data.id');
        $this->getJson($this->base)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($this->base.'/today')->assertOk()->assertJsonCount(1, 'data');
        DB::connection('tenant')->table('services')->where('id', $this->payload['service_id'])->update(['price' => 999]);
        $this->getJson($this->base.'/'.$id)->assertOk()->assertJsonPath('data.price', '50.00');
        foreach (['done', 'pending', 'confirmed'] as $status) {
            $this->putJson($this->base.'/'.$id, ['status' => $status])->assertOk()->assertJsonPath('data.status', $status);
        }
        $this->patchJson($this->base.'/'.$id.'/cancel')->assertOk();
        $this->getJson($this->base.'/'.$id)->assertJsonPath('data.status', 'cancelled');
        $this->deleteJson($this->base.'/'.$id)->assertOk();
        $this->getJson($this->base.'/'.$id)->assertNotFound();
    }

    public function test_validation_and_overlapping_appointments(): void
    {
        $this->postJson($this->base, [])->assertUnprocessable()->assertJsonValidationErrors(['client_id', 'team_member_id', 'service_id', 'date', 'start_time']);
        $this->postJson($this->base, array_replace($this->payload, ['client_id' => (string) Str::uuid()]))->assertUnprocessable();
        $id = $this->postJson($this->base, $this->payload)->assertCreated()->json('data.id');
        $this->postJson($this->base, array_replace($this->payload, ['start_time' => '10:30']))->assertUnprocessable();
        $this->postJson($this->base, array_replace($this->payload, ['start_time' => '10:45']))->assertCreated();
        $this->patchJson($this->base.'/'.$id.'/cancel')->assertOk();
        $this->postJson($this->base, $this->payload)->assertCreated();
        $this->putJson($this->base.'/'.$id, ['status' => 'invalid'])->assertUnprocessable();
    }

    protected function tearDown(): void
    {
        DB::purge('tenant');
        unlink($this->tenantDatabase);
        parent::tearDown();
    }

    public function test_foreign_tenant_and_guest_are_rejected(): void
    {
        $other = User::factory()->create();
        $this->getJson('/api/companies/'.$other->tenant_id.'/appointments')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->getJson($this->base)->assertUnauthorized();
    }
}
