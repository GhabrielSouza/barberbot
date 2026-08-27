<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    /**
     * List all tenants, optionally filtered by status.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->list($request->query('status'));

        return TenantResource::collection($tenants);
    }

    /**
     * Create a new tenant. Schema provisioning is enqueued automatically.
     *
     * @param CreateTenantRequest $request
     * @return TenantResource
     */
    public function store(CreateTenantRequest $request): TenantResource
    {
        $tenant = $this->tenantService->create($request->validated());

        return new TenantResource($tenant);
    }

    /**
     * Get tenant details.
     *
     * @param Tenant $tenant
     * @return TenantResource
     */
    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant);
    }

    /**
     * Update tenant profile fields.
     *
     * @param UpdateTenantRequest $request
     * @param Tenant $tenant
     * @return TenantResource
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant = $this->tenantService->update($tenant, $request->validated());

        return new TenantResource($tenant);
    }

    /**
     * Cancel a tenant (soft-remove, keeps the Postgres schema intact).
     *
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function cancel(Tenant $tenant): JsonResponse
    {
        $this->tenantService->cancel($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Tenant cancelled',
        ]);
    }

    /**
     * Permanently delete a tenant catalog record.
     *
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully',
        ]);
    }
}
