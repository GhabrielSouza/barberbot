<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * List all services for a company.
     *
     * The current tenant schema does not include company_id in services,
     * so the list is not filtered by foreign key.
     */
    public function index(Tenant $company): AnonymousResourceCollection
    {
        $services = Service::query()
            ->orderBy('name')
            ->paginate(15);

        return ServiceResource::collection($services);
    }

    /**
     * Get active services only.
     */
    public function active(Tenant $company): AnonymousResourceCollection
    {
        $services = Service::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Create a new service.
     */
    public function store(CreateServiceRequest $request, Tenant $company): ServiceResource
    {
        $duration = $request->input('duration_minutes', $request->input('duration_min', 0));

        $service = Service::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration_min' => (int) $duration,
            'category' => $request->category,
            'active' => $request->active ?? true,
        ]);

        return new ServiceResource($service);
    }

    /**
     * Get service details.
     */
    public function show(Tenant $company, Service $service): ServiceResource
    {
        return new ServiceResource($service);
    }

    /**
     * Update service.
     */
    public function update(UpdateServiceRequest $request, Tenant $company, Service $service): ServiceResource
    {
        $data = $request->all();

        if (array_key_exists('duration_minutes', $data) || array_key_exists('duration_min', $data)) {
            $data['duration_min'] = (int) ($data['duration_minutes'] ?? $data['duration_min']);
            unset($data['duration_minutes']);
        }

        $service->update($data);

        return new ServiceResource($service->fresh());
    }

    /**
     * Toggle service active status.
     */
    public function toggleActive(Tenant $company, Service $service): JsonResponse
    {
        $service->update(['active' => ! $service->active]);

        return response()->json([
            'success' => true,
            'message' => 'Service status updated',
            'active' => $service->active,
        ]);
    }

    /**
     * Delete service.
     */
    public function destroy(Tenant $company, Service $service): JsonResponse
    {
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }
}
