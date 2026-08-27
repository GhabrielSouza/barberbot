<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    /**
     * List schedules for a barber.
     *
     * @param Barber $barber
     * @return Collection
     */
    public function listByBarber(Barber $barber): Collection
    {
        return Schedule::where('barber_id', $barber->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for a specific day of week.
     *
     * @param Barber $barber
     * @param int $dayOfWeek
     * @return Collection
     */
    public function listByDay(Barber $barber, int $dayOfWeek): Collection
    {
        return Schedule::where('barber_id', $barber->id)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Create a new schedule for a barber.
     *
     * @param Barber $barber
     * @param array $data
     * @return Schedule
     */
    public function createSchedule(Barber $barber, array $data): Schedule
    {
        return Schedule::create([
            'barber_id' => $barber->id,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);
    }

    /**
     * Update an existing schedule.
     *
     * @param Schedule $schedule
     * @param array $data
     * @return Schedule
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        $schedule->update(array_intersect_key($data, array_flip([
            'day_of_week',
            'start_time',
            'end_time',
        ])));

        return $schedule;
    }

    /**
     * Delete a schedule.
     *
     * @param Schedule $schedule
     * @return bool|null
     */
    public function deleteSchedule(Schedule $schedule): ?bool
    {
        return $schedule->delete();
    }

    /**
     * Bulk create schedules for a barber (replaces existing).
     *
     * @param Barber $barber
     * @param array $schedulesData
     * @return Collection
     */
    public function bulkCreateSchedules(Barber $barber, array $schedulesData): Collection
    {
        // Delete existing schedules
        Schedule::where('barber_id', $barber->id)->delete();

        // Create new schedules
        $created = [];
        foreach ($schedulesData as $data) {
            $created[] = $this->createSchedule($barber, $data);
        }

        return collect($created);
    }
}
