<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Pagination\LengthAwarePaginator;

class PlanService
{
    /**
     * List plans, optionally filtered by active status.
     */
    public function list(?bool $active = null, int $perPage = 15): LengthAwarePaginator
    {
        return Plan::when($active !== null, fn ($query) => $query->where('active', $active))
            ->orderBy('price_month')
            ->paginate($perPage);
    }

    /**
     * Create a new plan.
     */
    public function create(array $data): Plan
    {
        return Plan::create([
            'name' => $data['name'],
            'price_month' => $data['price_month'],
            'included_members' => $data['included_members'],
            'price_per_extra_member' => $data['price_per_extra_member'],
            'limits' => $data['limits'] ?? null,
            'active' => $data['active'] ?? true,
        ]);
    }

    /**
     * Update plan fields.
     */
    public function update(Plan $plan, array $data): Plan
    {
        $plan->update(array_intersect_key($data, array_flip([
            'name', 'price_month', 'included_members', 'price_per_extra_member', 'limits', 'active',
        ])));

        return $plan;
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive(Plan $plan): Plan
    {
        $plan->update(['active' => ! $plan->active]);

        return $plan;
    }

    /**
     * Permanently delete a plan.
     */
    public function delete(Plan $plan): void
    {
        $plan->delete();
    }
}
