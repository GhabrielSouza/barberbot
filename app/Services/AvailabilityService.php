<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available time slots for a barber on a specific date
     *
     * @param Barber $barber
     * @param Carbon $date
     * @param Service $service
     * @return array
     */
    public function getAvailableSlotsForBarber(Barber $barber, Carbon $date, Service $service): array
    {
        $dayOfWeek = $date->dayOfWeek;

        // Get barber's schedule for this day
        $schedule = Schedule::where('barber_id', $barber->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return []; // Barber doesn't work on this day
        }

        $slots = [];
        $startTime = Carbon::createFromFormat('H:i', $schedule->start_time);
        $endTime = Carbon::createFromFormat('H:i', $schedule->end_time);
        $serviceDuration = $service->duration_minutes;

        // Get all appointments for this barber on this date
        $appointments = Appointment::where('barber_id', $barber->id)
            ->where('date', $date->format('Y-m-d'))
            ->where('status', '!=', 'canceled')
            ->get();

        $currentTime = clone $startTime;

        while ($currentTime->copy()->addMinutes($serviceDuration) <= $endTime) {
            $slotEnd = $currentTime->copy()->addMinutes($serviceDuration);

            // Check if this slot conflicts with any appointment
            $hasConflict = $appointments->some(function ($appointment) use ($currentTime, $slotEnd) {
                $appointmentStart = Carbon::createFromFormat('H:i', $appointment->time);
                $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->service->duration_minutes);

                return $currentTime < $appointmentEnd && $slotEnd > $appointmentStart;
            });

            if (!$hasConflict) {
                $slots[] = [
                    'time' => $currentTime->format('H:i'),
                    'available' => true,
                ];
            }

            $currentTime->addMinutes(30); // 30 min intervals
        }

        return $slots;
    }

    /**
     * Get available barbers for a service on a specific date
     *
     * @param Service $service
     * @param Carbon $date
     * @return array
     */
    public function getAvailableBarbersForService(Service $service, Carbon $date): array
    {
        $barbers = Barber::where('company_id', $service->company_id)
            ->where('active', true)
            ->get();

        $available = [];

        foreach ($barbers as $barber) {
            $slots = $this->getAvailableSlotsForBarber($barber, $date, $service);
            if (count($slots) > 0) {
                $available[] = [
                    'id' => $barber->id,
                    'name' => $barber->name,
                    'slots_count' => count($slots),
                ];
            }
        }

        return $available;
    }

    /**
     * Check if a specific time slot is available
     *
     * @param Barber $barber
     * @param Carbon $date
     * @param string $time (H:i format)
     * @param Service $service
     * @return bool
     */
    public function isSlotAvailable(Barber $barber, Carbon $date, string $time, Service $service): bool
    {
        $slots = $this->getAvailableSlotsForBarber($barber, $date, $service);
        return collect($slots)->pluck('time')->contains($time);
    }
}
