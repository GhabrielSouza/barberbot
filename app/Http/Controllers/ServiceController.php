<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * List all services for a company
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function index(Company $company): AnonymousResourceCollection
    {
        $services = Service::where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(15);

        return ServiceResource::collection($services);
    }

    /**
     * Get active services only
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function active(Company $company): AnonymousResourceCollection
    {
        $services = Service::where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Create a new service
     *
     * @param CreateServiceRequest $request
     * @param Company $company
     * @return ServiceResource
     */
    public function store(CreateServiceRequest $request, Company $company): ServiceResource
    {
        $service = Service::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration_minutes' => $request->duration_minutes,
            'company_id' => $company->id,
            'active' => $request->active ?? true,
        ]);

        return new ServiceResource($service);
    }

    /**
     * Get service details
     *
     * @param Company $company
     * @param Service $service
     * @return ServiceResource
     */
    public function show(Company $company, Service $service): ServiceResource
    {
        if ($service->company_id !== $company->id) {
            abort(403);
        }

        return new ServiceResource($service);
    }

    /**
     * Update service
     *
     * @param UpdateServiceRequest $request
     * @param Company $company
     * @param Service $service
     * @return ServiceResource
     */
    public function update(UpdateServiceRequest $request, Company $company, Service $service): ServiceResource
    {
        if ($service->company_id !== $company->id) {
            abort(403);
        }

        $service->update($request->only(['name', 'price', 'duration_minutes', 'active']));

        return new ServiceResource($service);
    }

    /**
     * Toggle service active status
     *
     * @param Company $company
     * @param Service $service
     * @return JsonResponse
     */
    public function toggleActive(Company $company, Service $service): JsonResponse
    {
        if ($service->company_id !== $company->id) {
            abort(403);
        }

        $service->update(['active' => !$service->active]);

        return response()->json([
            'success' => true,
            'message' => 'Service status updated',
            'active' => $service->active,
        ]);
    }

    /**
     * Delete service
     *
     * @param Company $company
     * @param Service $service
     * @return JsonResponse
     */
    public function destroy(Company $company, Service $service): JsonResponse
    {
        if ($service->company_id !== $company->id) {
            abort(403);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }
}
