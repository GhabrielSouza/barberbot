<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBarberRequest;
use App\Http\Requests\UpdateBarberRequest;
use App\Http\Resources\BarberResource;
use App\Models\Barber;
use App\Models\Company;
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
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function index(Company $company): AnonymousResourceCollection
    {
        return BarberResource::collection($this->barberService->listBarbers($company));
    }

    /**
     * Get active barbers only
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function active(Company $company): AnonymousResourceCollection
    {
        return BarberResource::collection($this->barberService->listActiveBarbers($company));
    }

    /**
     * Create a new barber
     *
     * @param CreateBarberRequest $request
     * @param Company $company
     * @return BarberResource
     */
    public function store(CreateBarberRequest $request, Company $company): BarberResource
    {
        $barber = $this->barberService->createBarber($company, $request->validated());

        return new BarberResource($barber);
    }

    /**
     * Get barber details
     *
     * @param Company $company
     * @param Barber $barber
     * @return BarberResource
     */
    public function show(Company $company, Barber $barber): BarberResource
    {
        $barber->load(['schedules', 'appointments']);

        return new BarberResource($barber);
    }

    /**
     * Update barber
     *
     * @param UpdateBarberRequest $request
     * @param Company $company
     * @param Barber $barber
     * @return BarberResource
     */
    public function update(UpdateBarberRequest $request, Company $company, Barber $barber): BarberResource
    {
        $barber = $this->barberService->updateBarber($company, $barber, $request->validated());

        $barber->load('schedules');
        return new BarberResource($barber);
    }

    /**
     * Toggle barber active status
     *
     * @param Company $company
     * @param Barber $barber
     * @return JsonResponse
     */
    public function toggleActive(Company $company, Barber $barber): JsonResponse
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
     *
     * @param Company $company
     * @param Barber $barber
     * @return JsonResponse
     */
    public function destroy(Company $company, Barber $barber): JsonResponse
    {
        $this->barberService->deleteBarber($company, $barber);

        return response()->json([
            'success' => true,
            'message' => 'Barber deleted successfully',
        ]);
    }
}
