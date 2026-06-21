<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CATÁLOGO DE TENANTS — schema public
 *
 * Multi-tenant: cada assinante (empresa) tem seu próprio schema PostgreSQL
 * dentro do mesmo database. Este schema `public` guarda APENAS o catálogo:
 * quem é o tenant, qual schema dele, e qual login global aponta pra ele.
 *
 * Convenções:
 *   - schema_name = "tenant_" + slug único (ex.: tenant_studio_rafa)
 *   - dados do tenant NUNCA entram aqui (eles vivem no schema dele)
 *   - users_global é o login cross-tenant (email único no banco inteiro)
 *
 * Conexão: a conexão default do Laravel usa o schema `public`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                  // "Studio Rafa"
            $table->string('slug')->unique();        // "studio-rafa"  → vira "tenant_studio_rafa"
            $table->string('schema_name')->unique();// já pronto p/ usar em SET search_path
            $table->string('segment')->nullable();   // "Barbearia & estética"
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('status', 20)->default('active'); // active|suspended|canceled
            $table->string('plan', 30)->default('trial');   // trial|pro|enterprise
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Login cross-tenant: o mesmo email não pode existir em 2 tenants.
        Schema::create('users_global', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->string('email')->unique();       // único no banco inteiro
            $table->string('password');              // bcrypt
            $table->string('name');
            $table->string('initials', 5)->nullable();
            $table->string('role', 20)->default('owner'); // owner|supervisor|atendente
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_global');
        Schema::dropIfExists('tenants');
    }
};