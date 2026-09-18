<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantDatabase;

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
    }

    protected function tearDown(): void
    {
        DB::purge('tenant');
        unlink($this->tenantDatabase);
        parent::tearDown();
    }

    public function test_metrics_use_tenant_tables_and_historical_prices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/companies/'.$user->tenant_id.'/dashboard/metrics')
            ->assertOk()->assertJsonPath('data.today_appointments', 4)
            ->assertJsonPath('data.total_clients', 1)->assertJsonPath('data.total_barbers', 1)
            ->assertJsonPath('data.total_services', 1)->assertJsonPath('data.monthly_revenue', 50)
            ->assertJsonPath('data.today_revenue', 50);
        $this->assertFalse(app()->bound('current_tenant'));
    }

    public function test_dashboard_endpoints_use_canonical_schema(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $base = '/api/companies/'.$user->tenant_id.'/dashboard/';
        $this->getJson($base.'appointments')->assertOk()->assertJsonPath('data.total', 4);
        $this->getJson($base.'revenue')->assertOk()->assertJsonPath('data.total_revenue', 50);
        $this->getJson($base.'top-services')->assertOk()->assertJsonPath('data.0.count', 1);
        $this->getJson($base.'barbers-performance')->assertOk()->assertJsonPath('data.0.total_revenue', 50);
        $this->getJson($base.'clients-stats')->assertOk()->assertJsonPath('data.active_clients', 1);
        $this->getJson($base.'appointments?start_date=invalid')->assertUnprocessable();
    }

    public function test_foreign_and_inactive_tenants_are_rejected_before_binding(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->getJson('/api/companies/'.$other->tenant_id.'/dashboard/metrics')->assertForbidden();
        $this->getJson('/api/companies/'.$other->tenant_id.'/services/service')->assertForbidden();
        $user->tenant->update(['status' => 'suspended']);
        $this->getJson('/api/companies/'.$user->tenant_id.'/dashboard/metrics')->assertForbidden();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/companies/anything/dashboard/metrics')->assertUnauthorized();
    }
}
