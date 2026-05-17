<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AppointmentService
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Create an appointment with validation
     *
     * @param User $user
     * @param Barber $barber
     * @param Service $service
     * @param Carbon $date
     * @param string $time
     * @return Appointment
     * @throws \Exception
     */
    public function createAppointment(User $user, Barber $barber, Service $service, Carbon $date, string $time): Appointment
    {
        // Validate company consistency
        if ($barber->company_id !== $user->company_id || $service->company_id !== $user->company_id) {
            throw new \Exception('Barber or service does not belong to the user\'s company');
        }

        // Validate slots availability
        if (!$this->availabilityService->isSlotAvailable($barber, $date, $time, $service)) {
            throw new \Exception('Selected time slot is not available');
        }

        // Check if appointment already exists
        $existingAppointment = Appointment::where('barber_id', $barber->id)
            ->where('date', $date->format('Y-m-d'))
            ->where('time', $time)
            ->where('status', '!=', 'canceled')
            ->first();

        if ($existingAppointment) {
            throw new \Exception('This time slot was just booked. Please select another one');
        }

        // Create appointment
        return Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'company_id' => $user->company_id,
            'date' => $date->format('Y-m-d'),
            'time' => $time,
            'status' => 'pending',
        ]);
    }

    /**
     * Confirm an appointment
     *
     * @param Appointment $appointment
     * @return Appointment
     */
    public function confirmAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'confirmed']);
        return $appointment;
    }

    /**
     * Cancel an appointment
     *
     * @param Appointment $appointment
     * @return Appointment
     */
    public function cancelAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'canceled']);
        return $appointment;
    }

    /**
     * Complete an appointment
     *
     * @param Appointment $appointment
     * @return Appointment
     */
    public function completeAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'completed']);
        return $appointment;
    }

    /**
     * Get today's appointments for a company
     *
     * @param int $companyId
     * @return Collection
     */
    public function getTodayAppointments(int $companyId): Collection
    {
        return Appointment::where('company_id', $companyId)
            ->today()
            ->with(['user', 'barber', 'service'])
            ->orderBy('time')
            ->get();
    }

    /**
     * Get appointments by date range
     *
     * @param int $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getAppointmentsByDateRange(int $companyId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Appointment::where('company_id', $companyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['user', 'barber', 'service'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    /**
     * Get total revenue for a date range
     *
     * @param int $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function getRevenueByDateRange(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        return Appointment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');
    }

    /**
     * Get most used services
     *
     * @param int $companyId
     * @param int $limit
     * @return array
     */
    public function getMostUsedServices(int $companyId, int $limit = 5): array
    {
        return Appointment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->select('service_id')
            ->groupBy('service_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->with('service')
            ->get()
            ->map(fn ($appointment) => [
                'service_id' => $appointment->service->id,
                'service_name' => $appointment->service->name,
                'count' => Appointment::where('service_id', $appointment->service_id)
                    ->where('status', 'completed')
                    ->count(),
            ])
            ->toArray();
    }
}
