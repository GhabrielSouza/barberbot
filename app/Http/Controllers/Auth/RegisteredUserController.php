<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->tenant_name,
                'slug' => $this->generateUniqueSlug($request->tenant_name),
                'schema_name' => $this->generateSchemaName($request->tenant_name),
                'status' => 'active',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->string('password')),
                'role' => 'owner',
            ]);

            $user->load('tenant');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'tenant_id']),
            'tenant' => $user->tenant?->only(['id', 'name', 'slug', 'schema_name', 'status']),
        ], 201);
    }

    /**
     * Generate a unique slug for the tenant.
     */
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

    /**
     * Generate a unique schema name for the tenant.
     */
    private function generateSchemaName(string $name): string
    {
        $base = 'tenant_'.Str::snake(Str::slug($name), '_');
        $schema = $base;
        $counter = 1;

        while (Tenant::where('schema_name', $schema)->exists()) {
            $schema = $base.'_'.$counter++;
        }

        return $schema;
    }
}
