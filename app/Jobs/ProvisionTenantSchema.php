<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Provisiona o schema de um tenant:
 *   1. CREATE SCHEMA "tenant_X" (idempotente)
 *   2. Aponta a conexão 'tenant' pro search_path correto
 *   3. Roda as migrations de tenant dentro desse schema
 *
 * Disparado por:
 *   - TenantObserver (created)        → quando um Tenant::create() roda
 *   - IdentifyTenant middleware       → lazy retry se tenant está pending/failed
 *   - TenantsMigrate command          → loop manual em todos os tenants
 *
 * Idempotente: se já existe schema/migration rodadas, não faz nada duplicado.
 */
class ProvisionTenantSchema implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 3 tentativas com backoff: 30s, 2min, 10min.
     * Erros típicos: lock do Postgres, conexão caiu, deadlock.
     */
    public int $tries = 3;
    public int $timeout = 180;

    /**
     * @param string $tenantId UUID do Tenant (em public.tenants).
     * @param bool   $fresh   Se true, dropa o schema antes de recriar (CUIDADO: apaga dados).
     */
    public function __construct(
        public string $tenantId,
        public bool $fresh = false,
    ) {}

    /**
     * Backoff entre tentativas (segundos).
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(): void
    {
        $tenant = DB::table('tenants')->where('id', $this->tenantId)->first();

        if (! $tenant) {
            throw new RuntimeException("Tenant {$this->tenantId} não encontrado em public.tenants.");
        }

        $schema = $tenant->schema_name;

        Log::info('Iniciando provisionamento de schema', [
            'tenant_id'   => $this->tenantId,
            'schema'      => $schema,
            'fresh'       => $this->fresh,
            'tentativa'   => $this->attempts(),
        ]);

        // 1. (Opcional) dropa o schema pra reconstruir do zero
        if ($this->fresh) {
            DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
            Log::warning("Schema {$schema} dropado (fresh=true)");
        }

        // 2. Cria o schema (idempotente). Usa a conexão default porque CREATE
        //    SCHEMA não aceita search_path dinâmico — é DDL no nível do DB.
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");

        // 3. Aponta a conexão 'tenant' pro schema. Após DB::purge, o Laravel
        //    recria a conexão lendo a config atualizada.
        $this->pointConnectionTo($schema);

        // 4. Roda as migrations de tenant. O 000010 (bootstrap) faz
        //    SET search_path TO "<schema>" e o restante roda dentro dele.
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "migrate falhou (exit={$exitCode}) para schema {$schema}: " . Artisan::output()
            );
        }

        Log::info('Schema provisionado com sucesso', [
            'tenant_id' => $this->tenantId,
            'schema'    => $schema,
        ]);
    }

    /**
     * Chamado quando todas as tentativas ($tries) esgotam.
     * Marca o tenant com um erro visível pra alguém investigar manualmente.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProvisionTenantSchema falhou definitivamente', [
            'tenant_id' => $this->tenantId,
            'erro'      => $exception->getMessage(),
        ]);

        // Marca no próprio registro do tenant que houve falha de provisionamento
        // (campo extra na tabela 'tenants'; se não existir ainda, fica no log).
        try {
            DB::table('tenants')
                ->where('id', $this->tenantId)
                ->update([
                    'status'              => 'suspended',
                    'provisioning_error'  => substr($exception->getMessage(), 0, 500),
                    'updated_at'          => now(),
                ]);
        } catch (\Throwable $e) {
            Log::error('Não consegui marcar tenant como suspended', [
                'tenant_id' => $this->tenantId,
                'erro'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Aponta a conexão 'tenant' pro schema informado e força recriação do PDO.
     * Lê a chave `schema` da config (sincronizada com o `search_path`).
     */
    private function pointConnectionTo(string $schema): void
    {
        config([
            'database.connections.tenant.schema'      => $schema,
            'database.connections.tenant.search_path' => $schema,
        ]);
        DB::purge('tenant');
    }
}