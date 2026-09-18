<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * List all appointments for a company
     */
    public function index(Tenant $company): AnonymousResourceCollection
    {
        $appointments = Appointment::query()
            ->with(['client', 'barber', 'service'])
            ->orderBy('date', 'desc')
            ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Get today's appointments
     */
    public function today(Tenant $company): AnonymousResourceCollection
    {
        $appointments = $this->appointmentService->getTodayAppointments($company->id);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Create a new appointment
     *
     * @return AppointmentResource|JsonResponse
     */
    public function store(CreateAppointmentRequest $request, Tenant $company)
    {
        $appointment = $this->appointmentService->createForTenant($request->validated());

        $appointment->load(['client', 'barber', 'service']);

        return new AppointmentResource($appointment);
    }

    /**
     * Get appointment details
     */
    public function show(Tenant $company, Appointment $appointment): AppointmentResource
    {
        $appointment->load(['client', 'barber', 'service']);

        return new AppointmentResource($appointment);
    }

    /**
     * Update appointment status
     *
     * @return AppointmentResource|JsonResponse
     */
    public function update(UpdateAppointmentRequest $request, Tenant $company, Appointment $appointment)
    {
        match ($request->status) {
            'confirmed' => $this->appointmentService->confirmAppointment($appointment),
            'cancelled' => $this->appointmentService->cancelAppointment($appointment),
            'done' => $this->appointmentService->completeAppointment($appointment),
            default => $appointment->update(['status' => 'pending']),
        };

        $appointment->load(['client', 'barber', 'service']);

        return new AppointmentResource($appointment);
    }

    /**
     * Cancel appointment
     */
    public function cancel(Tenant $company, Appointment $appointment): JsonResponse
    {
        $this->appointmentService->cancelAppointment($appointment);

        return response()->json([
            'success' => true,
            'message' => 'Appointment canceled successfully',
        ]);
    }

    /**
     * Delete appointment
     */
    public function destroy(Tenant $company, Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
