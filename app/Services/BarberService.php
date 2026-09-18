<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BarberService
{
    /**
     * List all barbers for the current tenant schema.
     */
    public function listBarbers(Tenant $company, int $perPage = 15): LengthAwarePaginator
    {
        return Barber::with('schedules')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get active barbers for the current tenant schema.
     */
    public function listActiveBarbers(Tenant $company): Collection
    {
        return Barber::where('active', true)
            ->with('schedules')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new barber.
     */
    public function createBarber(Tenant $company, array $data): Barber
    {
        $payload = array_merge([
            'active' => true,
            'is_admin' => false,
        ], array_intersect_key($data, array_flip([
            'name',
            'role',
            'color',
            'is_admin',
            'user_id',
            'active',
        ])));

        return Barber::create($payload);
    }

    /**
     * Update an existing barber.
     */
    public function updateBarber(Tenant $company, Barber $barber, array $data): Barber
    {
        $barber->update(array_intersect_key($data, array_flip([
            'name',
            'role',
            'color',
            'is_admin',
            'user_id',
            'active',
        ])));

        return $barber;
    }

    /**
     * Toggle the active status of a barber.
     */
    public function toggleActive(Tenant $company, Barber $barber): Barber
    {
        $barber->update(['active' => ! $barber->active]);

        return $barber;
    }

    /**
     * Delete a barber.
     */
    public function deleteBarber(Tenant $company, Barber $barber): ?bool
    {
        return $barber->delete();
    }
}
