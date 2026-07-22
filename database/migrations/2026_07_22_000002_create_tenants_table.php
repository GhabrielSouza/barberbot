<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenants — a empresa, 1 tenant = 1 empresa (schema public).
 * Ver MODELO-BANCO-DE-DADOS.md §2. `slug`, `schema_name` e `provisioning_error`
 * não estão no MD (que deriva o schema de `id`); mantidos aqui porque a
 * infra de provisionamento (App\Jobs\ProvisionTenantSchema, IdentifyTenant
 * middleware) já os usa para resolver e identificar o schema do tenant.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('schema_name')->unique();
            $table->string('segment')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('status', 20)->default('active'); // active|suspended|cancelled
            $table->string('provisioning_error', 500)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
