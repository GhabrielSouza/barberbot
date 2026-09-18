<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScheduleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected string $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantDatabase = tempnam(sys_get_temp_dir(), 'barber-tenant-');
        config(['database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->tenantDatabase, 'prefix' => '', 'foreign_key_constraints' => false]]);
        $schema = Schema::connection('tenant');
        $connection = DB::getDefaultConnection();
        DB::setDefaultConnection('tenant');
        try {
            Schema::create('team_members', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
            (require database_path('migrations/tenant/2026_09_18_000025_create_schedules_table.php'))->up();
        } finally {
            DB::setDefaultConnection($connection);
        }
    }

    protected function tearDown(): void
    {
        DB::purge('tenant');
        unlink($this->tenantDatabase);
        parent::tearDown();
    }

    public function test_schedule_crud_bulk_and_active_barbers(): void
    {
        $user = User::factory()->create();
        $barber = Barber::create(['name' => 'Barbeiro', 'active' => true]);
        $other = Barber::create(['name' => 'Outro', 'active' => true]);
        $this->actingAs($user);
        $base = '/api/companies/'.$user->tenant_id.'/barbers/'.$barber->id.'/schedules';
        $data = ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'];
        $id = $this->postJson($base, $data)->assertCreated()->json('data.id');
        $this->getJson($base)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($base.'/day/1')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($base.'/'.$id)->assertOk();
        $this->putJson($base.'/'.$id, ['end_time' => '18:00'])->assertOk();
        $this->putJson($base.'/'.$id, ['start_time' => '19:00'])->assertUnprocessable();
        $wrong = '/api/companies/'.$user->tenant_id.'/barbers/'.$other->id.'/schedules/'.$id;
        $this->getJson($wrong)->assertNotFound();
        $this->putJson($wrong, $data)->assertNotFound();
        $this->deleteJson($wrong)->assertNotFound();
        $this->postJson($base.'/bulk', ['schedules' => [array_replace($data, ['end_time' => '08:00'])]])->assertUnprocessable();
        $this->getJson($base)->assertJsonCount(1, 'data');
        $this->postJson($base.'/bulk', ['schedules' => [$data, array_replace($data, ['day_of_week' => 2])]])->assertOk();
        $this->getJson($base)->assertJsonCount(2, 'data');
        $this->getJson('/api/companies/'.$user->tenant_id.'/barbers/active')->assertOk()->assertJsonCount(2, 'data');
        $id = $this->getJson($base)->json('data.0.id');
        $this->deleteJson($base.'/'.$id)->assertOk();
        $this->getJson($base.'/'.$id)->assertNotFound();
        $foreign = User::factory()->create();
        $this->getJson('/api/companies/'.$foreign->tenant_id.'/barbers/active')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->getJson($base)->assertUnauthorized();
    }
}
