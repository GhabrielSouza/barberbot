<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBarberRequest;
use App\Http\Requests\UpdateBarberRequest;
use App\Http\Resources\BarberResource;
use App\Models\Barber;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarberController extends Controller
{
    /**
     * List all barbers for a company
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function index(Company $company): AnonymousResourceCollection
    {
        $barbers = Barber::where('company_id', $company->id)
            ->with('schedules')
            ->orderBy('name')
            ->paginate(15);

        return BarberResource::collection($barbers);
    }

    /**
     * Get active barbers only
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function active(Company $company): AnonymousResourceCollection
    {
        $barbers = Barber::where('company_id', $company->id)
            ->where('active', true)
            ->with('schedules')
            ->orderBy('name')
            ->get();

        return BarberResource::collection($barbers);
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
        $barber = Barber::create([
            'name' => $request->name,
            'company_id' => $company->id,
            'active' => $request->active ?? true,
        ]);

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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $barber->update($request->only(['name', 'active']));

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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $barber->update(['active' => !$barber->active]);

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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $barber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barber deleted successfully',
        ]);
    }
}
