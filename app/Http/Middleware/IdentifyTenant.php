<?php

namespace App\Http\Middleware;

use App\Jobs\ProvisionTenantSchema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o tenant ativo e configura a conexão 'tenant' pra apontar
 * pro schema correto.
 *
 * Como descobrir o tenant (em ordem):
 *   1. Header  "X-Tenant: <slug>"     ← uso programático (mobile, integração)
 *   2. Subdomínio  "<slug>.app.com"   ← produção
 *   3. URL segment  /{tenant}/...     ← fallback dev/staging
 *
 * Após identificar, faz 2 checagens opcionais:
 *   - Lazy retry: se o schema do tenant ainda não existe (ou foi marcado
 *     como suspended por falha de provisionamento), dispara o Job de
 *     reprocessamento sincronamente antes de continuar a request.
 *   - Seta `database.connections.tenant.schema` e `search_path` pro schema
 *     descoberto, e purga a conexão pra forçar recriação do PDO com o
 *     search_path novo.
 *
 * Importante: este middleware NÃO bloqueia a request — se não conseguir
 * identificar o tenant, segue sem mexer na conexão 'tenant' (deixa o
 * controller decidir o que fazer).
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            return $next($request);
        }

        // Lazy retry: schema não existe ou tenant foi suspenso por falha
        // anterior. Roda o Job de forma síncrona pra não devolver 503
        // desnecessariamente.
        if (! $this->schemaExists($tenant->schema_name) || $tenant->status === 'suspended') {
            try {
                (new ProvisionTenantSchema($tenant->id))->handle();

                // Recarrega o tenant (status pode ter mudado)
                $tenant = DB::table('tenants')->where('id', $tenant->id)->first();

                if (! $tenant || $tenant->status === 'suspended') {
                    return response()->json([
                        'error'   => 'Tenant não está ativo.',
                        'message' => 'Entre em contato com o suporte.',
                    ], 503);
                }
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'error'   => 'Falha ao provisionar tenant.',
                    'message' => 'Tente novamente em alguns instantes.',
                ], 503);
            }
        }

        // Aponta a conexão 'tenant' pro schema descoberto
        $this->pointConnectionTo($tenant->schema_name);

        // Expõe o tenant pros controllers via attribute e request
        $request->attributes->set('tenant', $tenant);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }

    /**
     * Procura o tenant por header → subdomínio → URL.
     */
    private function resolveTenant(Request $request): ?object
    {
        // 1. Header explícito
        if ($slug = $request->header('X-Tenant')) {
            return $this->lookupBySlug($slug);
        }

        // 2. Subdomínio (slug.app.com)
        $host = $request->getHost();
        if (substr_count($host, '.') >= 2) {
            $slug = strtolower(explode('.', $host)[0]);
            if ($slug && ! in_array($slug, ['www', 'api', 'admin'], true)) {
                return $this->lookupBySlug($slug);
            }
        }

        // 3. Segmento de URL: /{tenant}/... (primeiro segmento depois do prefixo /api)
        $segments = $request->segments();
        // Se a rota atual é /api/{tenant}/..., pega o primeiro segmento
        $first = $request->is('api/*') ? ($segments[1] ?? null) : ($segments[0] ?? null);

        if ($first && ! in_array($first, ['webhook', 'login', 'register', 'forgot-password'], true)) {
            return $this->lookupBySlug($first);
        }

        return null;
    }

    private function lookupBySlug(string $slug): ?object
    {
        return DB::table('tenants')->where('slug', $slug)->first();
    }

    /**
     * Verifica se o schema PostgreSQL existe (sem disparar exception).
     */
    private function schemaExists(string $schema): bool
    {
        try {
            $result = DB::selectOne(
                "SELECT 1 FROM information_schema.schemata WHERE schema_name = ?",
                [$schema]
            );

            return $result !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Seta schema + search_path na conexão 'tenant' e força recriação do PDO.
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