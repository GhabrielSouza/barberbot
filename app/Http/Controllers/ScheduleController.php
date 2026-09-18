<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Barber;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Get schedules for a barber
     */
    public function indexForBarber(Tenant $company, Barber $barber): AnonymousResourceCollection
    {
        return ScheduleResource::collection($this->scheduleService->listByBarber($barber));
    }

    /**
     * Create schedule for a barber
     */
    public function store(CreateScheduleRequest $request, Tenant $company, Barber $barber): ScheduleResource
    {
        $schedule = $this->scheduleService->createSchedule($barber, $request->validated());

        return new ScheduleResource($schedule);
    }

    /**
     * Get schedule details
     */
    public function show(Tenant $company, Barber $barber, Schedule $schedule): ScheduleResource
    {
        return new ScheduleResource($schedule);
    }

    /**
     * Update schedule
     */
    public function update(
        UpdateScheduleRequest $request,
        Tenant $company,
        Barber $barber,
        Schedule $schedule
    ): ScheduleResource {
        $schedule = $this->scheduleService->updateSchedule($schedule, $request->validated());

        return new ScheduleResource($schedule);
    }

    /**
     * Delete schedule
     */
    public function destroy(Tenant $company, Barber $barber, Schedule $schedule): JsonResponse
    {
        $this->scheduleService->deleteSchedule($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }

    /**
     * Get schedules by day of week
     *
     * @param  int  $day  (0-6)
     */
    public function byDay(Tenant $company, Barber $barber, int $day): AnonymousResourceCollection
    {
        return ScheduleResource::collection($this->scheduleService->listByDay($barber, $day));
    }

    /**
     * Bulk create schedules for a barber
     */
    public function bulkCreate(Tenant $company, Barber $barber): JsonResponse
    {
        $request = request();
        $schedulesData = $request->input('schedules', []);

        if (empty($schedulesData)) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules provided',
            ], 422);
        }

        $this->scheduleService->bulkCreateSchedules($barber, $schedulesData);

        return response()->json([
            'success' => true,
            'message' => 'Schedules updated successfully',
        ]);
    }
}
