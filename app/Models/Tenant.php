<?php

namespace App\Models;

use App\Observers\TenantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant do catálogo (schema public).
 *
 * Cada Tenant representa 1 assinante e possui 1 schema PostgreSQL próprio
 * (ex.: "tenant_studio_rafa"). Tudo que é dado de negócio vive nesse schema.
 *
 * Esta model mora na conexão default (public) e é APENAS o registro de catálogo.
 * Os dados de negócio (Company, User, Staff, Client, Appointment, ...) ficam
 * na conexão "tenant" — ver config/database.php.
 *
 * Status:
 *   - active    → provisionado e liberado
 *   - suspended → bloqueado pelo admin
 *   - canceled  → desativado definitivamente
 *
 * O provisionamento do schema é disparado automaticamente quando o model é
 * criado (ver TenantObserver → App\Jobs\ProvisionTenantSchema).
 */
#[ObservedBy([TenantObserver::class])]
class Tenant extends Model
{
    use HasUuids;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'schema_name',
        'segment',
        'city',
        'phone',
        'status',
        'plan',
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Helper: o tenant está utilizável?
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Helper: tenant novo que ainda não terminou o provisionamento.
     */
    public function isProvisionable(): bool
    {
        return in_array($this->status, ['active', 'trial'], true);
    }
}