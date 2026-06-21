<?php

/**
 * Setup de demonstração: cria 5 tenants e provisiona cada um.
 *
 * Uso: php setup_demo.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Jobs\ProvisionTenantSchema;

echo "═══════════════════════════════════════════════\n";
echo " SETUP DEMO — 5 tenants de exemplo\n";
echo "═══════════════════════════════════════════════\n\n";

$empresas = [
    ['name' => 'Studio Rafa',     'slug' => 'studio-rafa'],
    ['name' => 'Barbearia João',  'slug' => 'barbearia-joao'],
    ['name' => 'Salão Maria',     'slug' => 'salao-maria'],
    ['name' => 'Barbearia Lucas', 'slug' => 'barbearia-lucas'],
    ['name' => 'Estética Bela',   'slug' => 'estetica-bela'],
];

foreach ($empresas as $e) {
    $schema = 'tenant_' . str_replace('-', '_', $e['slug']);

    // Cria (ou reutiliza se já existir)
    $tenant = Tenant::firstOrCreate(
        ['slug' => $e['slug']],
        [
            'name'        => $e['name'],
            'schema_name' => $schema,
            'status'      => 'active',
        ]
    );

    // Provisiona (síncrono)
    try {
        (new ProvisionTenantSchema($tenant->id))->handle();
        echo "  ✓ {$e['slug']} → $schema\n";
    } catch (\Throwable $ex) {
        echo "  ✗ {$e['slug']}: " . $ex->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════════════\n";
echo " Total de tenants: " . DB::table('tenants')->count() . "\n";
echo "═══════════════════════════════════════════════\n";