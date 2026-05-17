<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Barber;
use App\Models\Company;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    /**
     * Get schedules for a barber
     *
     * @param Company $company
     * @param Barber $barber
     * @return AnonymousResourceCollection
     */
    public function indexForBarber(Company $company, Barber $barber): AnonymousResourceCollection
    {
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $schedules = Schedule::where('barber_id', $barber->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return ScheduleResource::collection($schedules);
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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $schedule = Schedule::create([
            'barber_id' => $barber->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

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
        if ($barber->company_id !== $company->id || $schedule->barber_id !== $barber->id) {
            abort(403);
        }

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
        if ($barber->company_id !== $company->id || $schedule->barber_id !== $barber->id) {
            abort(403);
        }

        $schedule->update($request->only(['day_of_week', 'start_time', 'end_time']));

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
        if ($barber->company_id !== $company->id || $schedule->barber_id !== $barber->id) {
            abort(403);
        }

        $schedule->delete();

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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $schedules = Schedule::where('barber_id', $barber->id)
            ->where('day_of_week', $day)
            ->orderBy('start_time')
            ->get();

        return ScheduleResource::collection($schedules);
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
        if ($barber->company_id !== $company->id) {
            abort(403);
        }

        $request = request();
        $schedules = $request->input('schedules', []);

        if (empty($schedules)) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules provided',
            ], 422);
        }

        // Delete existing schedules
        Schedule::where('barber_id', $barber->id)->delete();

        // Create new schedules
        foreach ($schedules as $schedule) {
            Schedule::create([
                'barber_id' => $barber->id,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedules updated successfully',
        ]);
    }
}
