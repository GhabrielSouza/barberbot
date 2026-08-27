<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionTenantSchema;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class TenantCreate extends Command
{
    protected $signature = 'tenant:create
                            {--name= : Nome do tenant/empresa}
                            {--slug= : Slug único do tenant (opcional, derivado do nome)}
                            {--user-name= : Nome do usuário owner}
                            {--user-email= : Email do usuário owner}
                            {--user-password= : Senha do usuário owner (opcional, gera uma aleatória)}';

    protected $description = 'Cria um novo tenant, provisiona o schema e cria o usuário owner.';

    public function handle(): int
    {
        $tenantName = $this->option('name') ?? $this->ask('Nome do tenant/empresa');
        $slug = $this->option('slug') ?: $this->generateUniqueSlug($tenantName);
        $schemaName = $this->generateUniqueSchemaName($tenantName);

        $userName = $this->option('user-name') ?? $this->ask('Nome do usuário owner');
        $userEmail = $this->option('user-email') ?? $this->ask('Email do usuário owner');
        $userPassword = $this->option('user-password') ?? $this->generatePassword();

        try {
            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => $slug,
                'schema_name' => $schemaName,
                'status' => 'active',
            ]);

            $this->info("Tenant criado: {$tenant->name} ({$tenant->slug})");
            $this->info("Provisionando schema {$schemaName}...");

            (new ProvisionTenantSchema($tenant->id))->handle();

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $userName,
                'email' => $userEmail,
                'password_hash' => Hash::make($userPassword),
                'role' => 'owner',
            ]);

            $this->newLine();
            $this->info('✓ Tenant provisionado com sucesso.');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Tenant ID', $tenant->id],
                    ['Tenant Name', $tenant->name],
                    ['Slug', $tenant->slug],
                    ['Schema', $tenant->schema_name],
                    ['Owner Email', $user->email],
                    ['Owner Password', $userPassword],
                ]
            );

            return SymfonyCommand::SUCCESS;
        } catch (Throwable $e) {
            $this->error('✗ Falha ao criar tenant: '.$e->getMessage());

            return SymfonyCommand::FAILURE;
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function generateUniqueSchemaName(string $name): string
    {
        $base = 'tenant_'.Str::snake(Str::slug($name), '_');
        $schema = $base;
        $counter = 1;

        while (Tenant::where('schema_name', $schema)->exists()) {
            $schema = $base.'_'.$counter++;
        }

        return $schema;
    }

    private function generatePassword(): string
    {
        return Str::random(12);
    }
}
