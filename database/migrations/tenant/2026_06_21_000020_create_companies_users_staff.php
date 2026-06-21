<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CORE DO TENANT — empresas, usuários e equipe
 *
 * Roda dentro do schema do tenant (search_path já ajustado pela migration
 * anterior). Tudo que esta migration cria fica isolado do schema public.
 */
return new class extends Migration {
    public function up(): void
    {
        // Empresa (espelho parcial da tabela public.tenants, para queries
        // locais sem precisar cruzar schema).
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('segment')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Usuários do tenant (login é via public.users_global, isto aqui
        // é o perfil de aplicação — evita re-login ao trocar de empresa).
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('global_user_id')->unique(); // FK lógica → public.users_global.id
            $table->string('name');
            $table->string('email');
            $table->string('initials', 5)->nullable();
            $table->string('role', 20)->default('owner'); // owner|supervisor|atendente
            $table->timestamps();

            $table->unique(['email']); // único dentro do tenant
        });

        // Equipe / profissionais (Staff). Pode ou não ser um user que loga.
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->uuid('user_id')->nullable(); // FK lógica → users.id (staff que também loga)
            $table->string('name');
            $table->string('initials', 5);
            $table->string('color', 9);           // "#cca96b"
            $table->string('role')->nullable();   // "barbeiro", "estética"
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};