<?php

namespace App\Observers;

use App\Jobs\ProvisionTenantSchema;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * Dispara o provisionamento do schema sempre que um Tenant é criado.
 *
 * ATENÇÃO — este observer SÓ dispara se o Tenant for criado via Eloquent
 * (Tenant::create() ou $tenant->save() num model novo). Se você criar via
 * DB::table('tenants')->insert([...]), o evento NÃO dispara e o schema
 * não é provisionado automaticamente — você precisa rodar:
 *
 *   php artisan tenants:migrate --tenant=<schema>
 *
 * Ou usar o middleware IdentifyTenant no próximo request autenticado, que
 * detecta schema faltando e reprocessa via lazy retry.
 */
class TenantObserver
{
    /**
     * Logo após o INSERT em public.tenants, agenda a criação do schema
     * e a execução das migrations de tenant. Vai pra fila — não bloqueia
     * a request do front.
     */
    public function created(Tenant $tenant): void
    {
        ProvisionTenantSchema::dispatch($tenant->id);

        Log::info('Tenant created — provisionamento enfileirado', [
            'tenant_id'   => $tenant->id,
            'schema_name' => $tenant->schema_name,
        ]);
    }
}