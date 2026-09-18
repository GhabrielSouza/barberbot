<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class TenantCrudTestCase extends TestCase
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
            (require database_path('migrations/tenant/2026_07_22_000014_create_clients_table.php'))->up();
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
}
