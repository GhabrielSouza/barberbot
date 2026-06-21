<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Jobs\ProvisionTenantSchema;

echo "════════════════════════════════════════════\n";
echo " SIMULAÇÃO: Criando tenant via Model Eloquent\n";
echo "════════════════════════════════════════════\n\n";

// ── Gera slug único baseado em timestamp pra não dar UNIQUE conflict ──
$slug   = 'sim-' . date('His');
$schema = 'tenant_' . str_replace('-', '_', $slug);

echo "[1/5] Verificando estado inicial...\n";
echo "  Tenants no catálogo: " . DB::table('tenants')->count() . "\n";
echo "  Jobs na fila:        " . DB::table('jobs')->count() . "\n";
echo "  Slug que vou usar:   $slug\n\n";

echo "[2/5] Criando Tenant via Model (vai disparar Observer)...\n";
$tenant = Tenant::create([
    'name'        => 'Simulação ' . date('H:i:s'),
    'slug'        => $slug,
    'schema_name' => $schema,
    'status'      => 'active',
]);
echo "  ✓ INSERT em public.tenants OK\n";
echo "  → ID:    " . $tenant->id . "\n";
echo "  → Slug:  " . $tenant->slug . "\n\n";

echo "[3/5] Verificando se Observer disparou o Job na fila...\n";
sleep(1); // dá tempo do Eloquent finalizar
$jobs = DB::table('jobs')->get();
echo "  Jobs na fila agora: " . $jobs->count() . "\n";
foreach ($jobs as $j) {
    echo "    → payload: " . substr($j->payload, 0, 80) . "...\n";
}
echo "\n";

echo "[4/5] Processando o Job (síncrono, sem queue:work)...\n";
(new ProvisionTenantSchema($tenant->id))->handle();
echo "  ✓ Job executado\n\n";

echo "[5/5] Conferindo o schema criado...\n";
$tables = DB::select(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name",
    [$schema]
);
echo "  Tabelas no schema '$schema': " . count($tables) . "\n";
foreach ($tables as $t) {
    echo "    - " . $t->table_name . "\n";
}

echo "\n════════════════════════════════════════════\n";
echo " RESULTADO: Tenant " . $slug . " provisionado com sucesso\n";
echo "════════════════════════════════════════════\n";