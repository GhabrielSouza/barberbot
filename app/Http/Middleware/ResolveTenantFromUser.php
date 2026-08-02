<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant ativo a partir do usuário autenticado e aponta
 * a conexão 'tenant' para o schema correto.
 *
 * Diferente do IdentifyTenant (que resolve por header/subdomínio/URL),
 * este middleware assume que o usuário já está autenticado e usa o
 * tenant_id vinculado ao usuário.
 */
class ResolveTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'É necessário estar autenticado para acessar este recurso.',
            ], 401);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant não encontrado.',
                'message' => 'O usuário autenticado não possui um tenant vinculado.',
            ], 403);
        }

        if ($tenant->status !== 'active') {
            return response()->json([
                'error' => 'Tenant inativo.',
                'message' => 'O tenant vinculado a este usuário não está ativo.',
            ], 403);
        }

        $this->pointConnectionTo($tenant->schema_name);

        $request->attributes->set('tenant', $tenant);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }

    /**
     * Seta schema + search_path na conexão 'tenant' e força recriação do PDO.
     */
    private function pointConnectionTo(string $schema): void
    {
        Config::set([
            'database.connections.tenant.schema' => $schema,
            'database.connections.tenant.search_path' => $schema,
        ]);

        DB::purge('tenant');
    }
}
