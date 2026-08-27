<?php

namespace App\Services;

use App\Models\CommissionMethod;
use Illuminate\Database\Eloquent\Collection;

class CommissionMethodService
{
    /**
     * List all commission methods.
     */
    public function list(): Collection
    {
        return CommissionMethod::orderBy('name')->get();
    }

    /**
     * Create a new commission method.
     */
    public function create(array $data): CommissionMethod
    {
        return CommissionMethod::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Update commission method fields.
     */
    public function update(CommissionMethod $commissionMethod, array $data): CommissionMethod
    {
        $commissionMethod->update(array_intersect_key($data, array_flip([
            'code', 'name', 'description',
        ])));

        return $commissionMethod;
    }

    /**
     * Permanently delete a commission method.
     */
    public function delete(CommissionMethod $commissionMethod): void
    {
        $commissionMethod->delete();
    }
}
