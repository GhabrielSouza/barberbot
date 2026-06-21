<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionTenantSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Roda migrations em TODOS os schemas de tenant.
 *
 * Uso típico:
 *   php artisan tenants:migrate
 *   php artisan tenants:migrate --tenant=studio_rafa
 *   php artisan tenants:migrate --fresh               # dropa e recria cada schema (CUIDADO)
 *   php artisan tenants:migrate --chunk=100           # processa em lotes
 *   php artisan tenants:migrate --only-failed         # só tenants que falharam no observer
 *
 * Quando usar:
 *   - Depois de criar uma migration nova (deploy), pra aplicar nos N tenants
 *   - Pra reprovisionar um tenant específico:  --tenant=<slug>
 *   - Pra recriar do zero:                       --fresh --tenant=<slug>
 */
class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate
                            {--tenant= : Slug do schema específico (ex.: studio_rafa). Se vazio, roda em todos.}
                            {--fresh : Dropa o schema antes de recriar (APAGA TODOS OS DADOS do tenant).}
                            {--chunk=50 : Tamanho do lote ao iterar tenants.}
                            {--only-failed : Só processa tenants com status suspended.}';

    protected $description = 'Roda as migrations de tenant em todos os schemas (ou em um schema específico).';

    public function handle(): int
    {
        $onlyOne = (bool) $this->option('tenant');
        $fresh   = (bool) $this->option('fresh');
        $chunk   = max(1, (int) $this->option('chunk'));
        $onlyFailed = (bool) $this->option('only-failed');

        // Modo single-tenant: provisiona esse tenant e sai
        if ($onlyOne) {
            return $this->migrateOne((string) $this->option('tenant'), $fresh);
        }

        // Modo bulk: itera em chunks
        $query = DB::table('tenants')->orderBy('id');

        if ($onlyFailed) {
            $query->where('status', 'suspended');
        }

        $success = 0;
        $failed  = 0;
        $errors  = [];

        $query->chunk($chunk, function ($tenants) use (&$success, &$failed, &$errors) {
            foreach ($tenants as $tenant) {
                try {
                    $this->info("→ {$tenant->schema_name} ({$tenant->id})");

                    (new ProvisionTenantSchema($tenant->id, fresh: false))->handle();

                    $this->info("  ✓ OK");
                    $success++;
                } catch (Throwable $e) {
                    $this->error("  ✗ FALHOU: " . $e->getMessage());
                    $errors[] = [
                        'tenant_id' => $tenant->id,
                        'schema'    => $tenant->schema_name,
                        'error'     => $e->getMessage(),
                    ];
                    $failed++;
                }
            }
        });

        $this->newLine();
        $this->info("Resumo: {$success} sucesso(s), {$failed} falha(s).");

        if ($failed > 0) {
            $this->table(
                ['tenant_id', 'schema', 'error'],
                $errors
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Migra 1 tenant específico pelo slug.
     */
    private function migrateOne(string $slug, bool $fresh): int
    {
        $tenant = DB::table('tenants')->where('slug', $slug)->first();

        if (! $tenant) {
            $this->error("Tenant com slug '{$slug}' não encontrado.");

            return self::FAILURE;
        }

        $this->info("→ {$tenant->schema_name} ({$tenant->id})" . ($fresh ? ' [FRESH]' : ''));

        try {
            (new ProvisionTenantSchema($tenant->id, fresh: $fresh))->handle();

            $this->info("✓ OK");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("✗ FALHOU: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}