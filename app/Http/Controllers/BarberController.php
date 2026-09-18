<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBarberRequest;
use App\Http\Requests\UpdateBarberRequest;
use App\Http\Resources\BarberResource;
use App\Models\Barber;
use App\Models\Tenant;
use App\Services\BarberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarberController extends Controller
{
    protected BarberService $barberService;

    public function __construct(BarberService $barberService)
    {
        $this->barberService = $barberService;
    }

    /**
     * List all barbers for a company
     */
    public function index(Tenant $company): AnonymousResourceCollection
    {
        return BarberResource::collection($this->barberService->listBarbers($company));
    }

    /**
     * Get active barbers only
     */
    public function active(Tenant $company): AnonymousResourceCollection
    {
        return BarberResource::collection($this->barberService->listActiveBarbers($company));
    }

    /**
     * Create a new barber
     */
    public function store(CreateBarberRequest $request, Tenant $company): BarberResource
    {
        $barber = $this->barberService->createBarber($company, $request->validated());

        return new BarberResource($barber);
    }

    /**
     * Get barber details
     */
    public function show(Tenant $company, Barber $barber): BarberResource
    {
        $barber->load(['schedules', 'appointments']);

        return new BarberResource($barber);
    }

    /**
     * Update barber
     */
    public function update(UpdateBarberRequest $request, Tenant $company, Barber $barber): BarberResource
    {
        $barber = $this->barberService->updateBarber($company, $barber, $request->validated());

        $barber->load('schedules');

        return new BarberResource($barber);
    }

    /**
     * Toggle barber active status
     */
    public function toggleActive(Tenant $company, Barber $barber): JsonResponse
    {
        $barber = $this->barberService->toggleActive($company, $barber);

        return response()->json([
            'success' => true,
            'message' => 'Barber status updated',
            'active' => $barber->active,
        ]);
    }

    /**
     * Delete barber
     */
    public function destroy(Tenant $company, Barber $barber): JsonResponse
    {
        $this->barberService->deleteBarber($company, $barber);

        return response()->json([
            'success' => true,
            'message' => 'Barber deleted successfully',
        ]);
    }
}
