<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use RuntimeException;

class TenantService
{
    /**
     * List tenants, optionally filtered by status.
     */
    public function list(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new tenant. Slug and schema_name are derived from the name.
     * Schema provisioning is triggered automatically by TenantObserver.
     */
    public function create(array $data): Tenant
    {
        $slug = $this->uniqueSlug($data['name']);

        return Tenant::create([
            'name' => $data['name'],
            'slug' => $slug,
            'schema_name' => 'tenant_'.str_replace('-', '_', $slug),
            'segment' => $data['segment'] ?? null,
            'city' => $data['city'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update tenant profile fields. Slug/schema_name are immutable after
     * creation because the tenant's Postgres schema is already provisioned.
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update(array_intersect_key($data, array_flip([
            'name', 'segment', 'city', 'phone', 'status',
        ])));

        return $tenant;
    }

    /**
     * Soft-remove a tenant by cancelling it. The Postgres schema is kept
     * intact — data is not dropped from here.
     */
    public function cancel(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'cancelled']);

        return $tenant;
    }

    /**
     * Permanently delete the tenant catalog record.
     * Does NOT drop the tenant's Postgres schema — that's a manual/ops step.
     */
    public function delete(Tenant $tenant): void
    {
        if ($tenant->status !== 'cancelled') {
            throw new RuntimeException('Tenant must be cancelled before it can be permanently deleted.');
        }

        $tenant->delete();
    }

    /**
     * Generates a unique slug from the tenant name, appending -2, -3, ...
     * on collision.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
