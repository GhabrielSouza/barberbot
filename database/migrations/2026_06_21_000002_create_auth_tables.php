<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AUTENTICAÇÃO — tokens e reset de senha (schema public)
 *
 * Essas duas tabelas vivem no `public` porque:
 *   - `personal_access_tokens` precisa ser consultada em toda request pra
 *     validar o token Sanctum — se ficasse no schema do tenant, o middleware
 *     teria que adivinhar o schema ANTES de saber quem é o user. Chicken-egg.
 *   - `password_reset_tokens` segue o mesmo raciocínio (login é cross-tenant).
 *
 * O Sanctum usa `morphs('tokenable')` — então o `tokenable_id` aponta pro
 * `public.users_global.id` (model App\Models\GlobalUser). Cada request:
 *   1. middleware acha o token → descobre o users_global
 *   2. users_global.tenant_id → public.tenants
 *   3. public.tenants.schema_name → seta search_path da conexão 'tenant'
 *   4. controller usa a conexão 'tenant' pro resto da request
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // morphs() já cria automaticamente o índice composto
            // (tokenable_type, tokenable_id). Não recriar manualmente.
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();         // hash SHA-256 do token puro
            $table->text('abilities')->nullable();         // JSON serializado
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');                        // hash do token de reset
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
    }
};