<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommissionMethodRequest;
use App\Http\Requests\UpdateCommissionMethodRequest;
use App\Http\Resources\CommissionMethodResource;
use App\Models\CommissionMethod;
use App\Services\CommissionMethodService;
use Illuminate\Http\JsonResponse;

class CommissionMethodController extends Controller
{
    public function __construct(protected CommissionMethodService $commissionMethodService) {}

    /**
     * List all commission methods.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $commissionMethods = $this->commissionMethodService->list();

        return CommissionMethodResource::collection($commissionMethods);
    }

    /**
     * Create a new commission method.
     *
     * @param CreateCommissionMethodRequest $request
     * @return CommissionMethodResource
     */
    public function store(CreateCommissionMethodRequest $request): CommissionMethodResource
    {
        $commissionMethod = $this->commissionMethodService->create($request->validated());

        return new CommissionMethodResource($commissionMethod);
    }

    /**
     * Get commission method details.
     *
     * @param CommissionMethod $commissionMethod
     * @return CommissionMethodResource
     */
    public function show(CommissionMethod $commissionMethod): CommissionMethodResource
    {
        return new CommissionMethodResource($commissionMethod);
    }

    /**
     * Update commission method fields.
     *
     * @param UpdateCommissionMethodRequest $request
     * @param CommissionMethod $commissionMethod
     * @return CommissionMethodResource
     */
    public function update(UpdateCommissionMethodRequest $request, CommissionMethod $commissionMethod): CommissionMethodResource
    {
        $commissionMethod = $this->commissionMethodService->update($commissionMethod, $request->validated());

        return new CommissionMethodResource($commissionMethod);
    }

    /**
     * Delete a commission method.
     *
     * @param CommissionMethod $commissionMethod
     * @return JsonResponse
     */
    public function destroy(CommissionMethod $commissionMethod): JsonResponse
    {
        $this->commissionMethodService->delete($commissionMethod);

        return response()->json([
            'success' => true,
            'message' => 'Commission method deleted successfully',
        ]);
    }
}
