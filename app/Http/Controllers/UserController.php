<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * List all users of a tenant, optionally filtered by role.
     *
     * @param Request $request
     * @param Tenant $tenant
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, Tenant $tenant): AnonymousResourceCollection
    {
        $users = $this->userService->list($tenant, $request->query('role'));

        return UserResource::collection($users);
    }

    /**
     * Create a new login user for the tenant.
     *
     * @param CreateUserRequest $request
     * @param Tenant $tenant
     * @return UserResource
     */
    public function store(CreateUserRequest $request, Tenant $tenant): UserResource
    {
        $user = $this->userService->create($tenant, $request->validated());

        return new UserResource($user);
    }

    /**
     * Get user details.
     *
     * @param Tenant $tenant
     * @param User $user
     * @return UserResource
     */
    public function show(Tenant $tenant, User $user): UserResource
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        return new UserResource($user);
    }

    /**
     * Update user fields.
     *
     * @param UpdateUserRequest $request
     * @param Tenant $tenant
     * @param User $user
     * @return UserResource
     */
    public function update(UpdateUserRequest $request, Tenant $tenant, User $user): UserResource
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    /**
     * Delete a user login.
     *
     * @param Tenant $tenant
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(Tenant $tenant, User $user): JsonResponse
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        $this->userService->delete($user);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
