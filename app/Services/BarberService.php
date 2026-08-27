<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BarberService
{
    /**
     * List all barbers for the current tenant schema.
     *
     * @param Company $company
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listBarbers(Company $company, int $perPage = 15): LengthAwarePaginator
    {
        return Barber::with('schedules')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get active barbers for the current tenant schema.
     *
     * @param Company $company
     * @return Collection
     */
    public function listActiveBarbers(Company $company): Collection
    {
        return Barber::where('active', true)
            ->with('schedules')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new barber.
     *
     * @param Company $company
     * @param array $data
     * @return Barber
     */
    public function createBarber(Company $company, array $data): Barber
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
     *
     * @param Company $company
     * @param Barber $barber
     * @param array $data
     * @return Barber
     */
    public function updateBarber(Company $company, Barber $barber, array $data): Barber
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
     *
     * @param Company $company
     * @param Barber $barber
     * @return Barber
     */
    public function toggleActive(Company $company, Barber $barber): Barber
    {
        $barber->update(['active' => !$barber->active]);

        return $barber;
    }

    /**
     * Delete a barber.
     *
     * @param Company $company
     * @param Barber $barber
     * @return bool|null
     */
    public function deleteBarber(Company $company, Barber $barber): ?bool
    {
        return $barber->delete();
    }
}
