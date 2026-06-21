<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BOOTSTRAP DE SCHEMA DE TENANT
 *
 * Cria fisicamente o schema PostgreSQL do assinante (ex.: tenant_studio_rafa)
 * e ajusta o search_path pra que TODAS as migrations seguintes vivam dentro dele.
 *
 * Laravel não tem multi-schema nativo; o truque é:
 *   1. CREATE SCHEMA "tenant_{slug}"
 *   2. SET search_path TO "tenant_{slug}", public  (na conexão)
 *
 * A partir daqui as migrations de tenant rodam nessa conexão/scope e tudo
 * que elas criam cai dentro do schema do assinante, isolado dos outros.
 *
 * Esta migration recebe o schema_name como argumento dinâmico (geralmente
 * chamado pelo provisionador após o INSERT em `public.tenants`).
 */
return new class extends Migration {
    public function up(): void
    {
        // schema_name vem de uma variável de ambiente/contexto; em produção
        // o job de provisioning seta antes de chamar a migration:
        //   DB_CONNECTION=tenant
        //   DB_SCHEMA=tenant_studio_rafa
        $schema = config('database.connections.tenant.schema')
                ?? env('DB_TENANT_SCHEMA');

        if (! $schema) {
            throw new RuntimeException(
                'DB_TENANT_SCHEMA não definido. O provisionador precisa setar antes de rodar esta migration.'
            );
        }

        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $schema . '"');
        DB::statement('SET search_path TO "' . $schema . '"');
    }

    public function down(): void
    {
        $schema = config('database.connections.tenant.schema')
                ?? env('DB_TENANT_SCHEMA');

        if ($schema) {
            DB::statement('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
        }
    }
};