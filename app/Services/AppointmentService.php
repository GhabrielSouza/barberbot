<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function createForTenant(array $data): Appointment
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            // Serialize bookings for this professional before checking overlaps.
            Barber::whereKey($data['team_member_id'])->lockForUpdate()->firstOrFail();
            $service = Service::findOrFail($data['service_id']);
            $start = Carbon::parse($data['date'].' '.$data['start_time']);
            $end = $start->copy()->addMinutes($service->duration_min);
            if ($service->duration_min < 1 || ! $start->isSameDay($end)) {
                throw ValidationException::withMessages([
                    'start_time' => 'O serviço deve terminar no mesmo dia e ter duração válida.',
                ]);
            }
            $conflict = Appointment::where('team_member_id', $data['team_member_id'])
                ->whereDate('date', $data['date'])->where('status', '!=', 'cancelled')
                ->where('start_time', '<', $end->format('H:i:s'))
                ->where('end_time', '>', $start->format('H:i:s'))->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'O horário selecionado já está ocupado.',
                ]);
            }

            return Appointment::create([
                'client_id' => $data['client_id'], 'team_member_id' => $data['team_member_id'],
                'service_id' => $service->id, 'service_name' => $service->name, 'price' => $service->price,
                'date' => $data['date'], 'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'), 'status' => 'confirmed',
            ]);
        });
    }

    /**
     * Create an appointment with validation
     *
     * @throws \Exception
     */
    public function createAppointment(User $user, Barber $barber, Service $service, Carbon $date, string $time): Appointment
    {
        // Validate company consistency
        if ($barber->company_id !== $user->company_id || $service->company_id !== $user->company_id) {
            throw new \Exception('Barber or service does not belong to the user\'s company');
        }

        // Validate slots availability
        if (! $this->availabilityService->isSlotAvailable($barber, $date, $time, $service)) {
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
     */
    public function confirmAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'confirmed']);

        return $appointment;
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'cancelled']);

        return $appointment;
    }

    /**
     * Complete an appointment
     */
    public function completeAppointment(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'done']);

        return $appointment;
    }

    /**
     * Get today's appointments for a company
     *
     * @param  int  $companyId
     */
    public function getTodayAppointments(string $companyId): Collection
    {
        return Appointment::query()
            ->today()
            ->with(['client', 'barber', 'service'])
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get appointments by date range
     */
    public function getAppointmentsByDateRange(int $companyId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Appointment::where('company_id', $companyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['client', 'barber', 'service'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get total revenue for a date range
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
