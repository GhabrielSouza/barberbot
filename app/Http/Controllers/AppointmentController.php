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
        $appointments = Appointment::where('company_id', $company->id)
            ->with(['user', 'barber', 'service'])
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
        try {
            $appointment = $this->appointmentService->createAppointment(
                $request->user,
                $request->barber,
                $request->service,
                $request->date,
                $request->time
            );

            $this->appointmentService->confirmAppointment($appointment);
            $appointment->load(['user', 'barber', 'service']);

            return new AppointmentResource($appointment);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get appointment details
     */
    public function show(Tenant $company, Appointment $appointment): AppointmentResource
    {
        if ($appointment->company_id !== $company->id) {
            abort(403);
        }

        $appointment->load(['user', 'barber', 'service']);

        return new AppointmentResource($appointment);
    }

    /**
     * Update appointment status
     *
     * @return AppointmentResource|JsonResponse
     */
    public function update(UpdateAppointmentRequest $request, Tenant $company, Appointment $appointment)
    {
        if ($appointment->company_id !== $company->id) {
            abort(403);
        }

        try {
            match ($request->status) {
                'confirmed' => $this->appointmentService->confirmAppointment($appointment),
                'canceled' => $this->appointmentService->cancelAppointment($appointment),
                'completed' => $this->appointmentService->completeAppointment($appointment),
                default => $appointment,
            };

            $appointment->load(['user', 'barber', 'service']);

            return new AppointmentResource($appointment);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(Tenant $company, Appointment $appointment): JsonResponse
    {
        if ($appointment->company_id !== $company->id) {
            abort(403);
        }

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
        if ($appointment->company_id !== $company->id) {
            abort(403);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
