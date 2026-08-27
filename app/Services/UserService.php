<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * List users of a tenant, optionally filtered by role.
     */
    public function list(Tenant $tenant, ?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('tenant_id', $tenant->id)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new login user for the tenant.
     */
    public function create(Tenant $tenant, array $data): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'owner',
        ]);
    }

    /**
     * Update user fields. Password is re-hashed only when provided.
     */
    public function update(User $user, array $data): User
    {
        $update = array_intersect_key($data, array_flip(['name', 'email', 'role']));

        if (! empty($data['password'])) {
            $update['password_hash'] = Hash::make($data['password']);
        }

        $user->update($update);

        return $user;
    }

    /**
     * Permanently delete a user login.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
