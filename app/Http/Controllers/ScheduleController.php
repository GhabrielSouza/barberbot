<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Barber;
use App\Models\Company;
use App\Models\Schedule;
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
     *
     * @param Company $company
     * @param Barber $barber
     * @return AnonymousResourceCollection
     */
    public function indexForBarber(Company $company, Barber $barber): AnonymousResourceCollection
    {
        return ScheduleResource::collection($this->scheduleService->listByBarber($barber));
    }

    /**
     * Create schedule for a barber
     *
     * @param CreateScheduleRequest $request
     * @param Company $company
     * @param Barber $barber
     * @return ScheduleResource
     */
    public function store(CreateScheduleRequest $request, Company $company, Barber $barber): ScheduleResource
    {
        $schedule = $this->scheduleService->createSchedule($barber, $request->validated());

        return new ScheduleResource($schedule);
    }

    /**
     * Get schedule details
     *
     * @param Company $company
     * @param Barber $barber
     * @param Schedule $schedule
     * @return ScheduleResource
     */
    public function show(Company $company, Barber $barber, Schedule $schedule): ScheduleResource
    {
        return new ScheduleResource($schedule);
    }

    /**
     * Update schedule
     *
     * @param UpdateScheduleRequest $request
     * @param Company $company
     * @param Barber $barber
     * @param Schedule $schedule
     * @return ScheduleResource
     */
    public function update(
        UpdateScheduleRequest $request,
        Company $company,
        Barber $barber,
        Schedule $schedule
    ): ScheduleResource {
        $schedule = $this->scheduleService->updateSchedule($schedule, $request->validated());

        return new ScheduleResource($schedule);
    }

    /**
     * Delete schedule
     *
     * @param Company $company
     * @param Barber $barber
     * @param Schedule $schedule
     * @return JsonResponse
     */
    public function destroy(Company $company, Barber $barber, Schedule $schedule): JsonResponse
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
     * @param Company $company
     * @param Barber $barber
     * @param int $day (0-6)
     * @return AnonymousResourceCollection
     */
    public function byDay(Company $company, Barber $barber, int $day): AnonymousResourceCollection
    {
        return ScheduleResource::collection($this->scheduleService->listByDay($barber, $day));
    }

    /**
     * Bulk create schedules for a barber
     *
     * @param Company $company
     * @param Barber $barber
     * @return JsonResponse
     */
    public function bulkCreate(Company $company, Barber $barber): JsonResponse
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

