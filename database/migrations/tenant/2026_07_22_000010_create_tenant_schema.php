<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bootstrap do schema PostgreSQL do tenant (ex.: tenant_studio_rafa).
 * Cria fisicamente o schema e ajusta o search_path pra que todas as
 * migrations seguintes deste diretório vivam dentro dele.
 * Ver MODELO-BANCO-DE-DADOS.md §1.
 */
return new class extends Migration {
    public function up(): void
    {
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
