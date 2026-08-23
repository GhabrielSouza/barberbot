<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function __construct(protected PlanService $planService) {}

    /**
     * List all plans, optionally filtered by active status.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $active = $request->has('active') ? $request->boolean('active') : null;

        $plans = $this->planService->list($active);

        return PlanResource::collection($plans);
    }

    /**
     * Get active plans only.
     *
     * @return AnonymousResourceCollection
     */
    public function active(): AnonymousResourceCollection
    {
        $plans = $this->planService->list(true);

        return PlanResource::collection($plans);
    }

    /**
     * Create a new plan.
     *
     * @param CreatePlanRequest $request
     * @return PlanResource
     */
    public function store(CreatePlanRequest $request): PlanResource
    {
        $plan = $this->planService->create($request->validated());

        return new PlanResource($plan);
    }

    /**
     * Get plan details.
     *
     * @param Plan $plan
     * @return PlanResource
     */
    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }

    /**
     * Update plan fields.
     *
     * @param UpdatePlanRequest $request
     * @param Plan $plan
     * @return PlanResource
     */
    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        $plan = $this->planService->update($plan, $request->validated());

        return new PlanResource($plan);
    }

    /**
     * Toggle plan active status.
     *
     * @param Plan $plan
     * @return JsonResponse
     */
    public function toggleActive(Plan $plan): JsonResponse
    {
        $plan = $this->planService->toggleActive($plan);

        return response()->json([
            'success' => true,
            'message' => 'Plan status updated',
            'active' => $plan->active,
        ]);
    }

    /**
     * Delete a plan.
     *
     * @param Plan $plan
     * @return JsonResponse
     */
    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->delete($plan);

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully',
        ]);
    }
}
