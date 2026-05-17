<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Company;
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
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function index(Company $company): AnonymousResourceCollection
    {
        $appointments = Appointment::where('company_id', $company->id)
            ->with(['user', 'barber', 'service'])
            ->orderBy('date', 'desc')
            ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Get today's appointments
     *
     * @param Company $company
     * @return AnonymousResourceCollection
     */
    public function today(Company $company): AnonymousResourceCollection
    {
        $appointments = $this->appointmentService->getTodayAppointments($company->id);
        return AppointmentResource::collection($appointments);
    }

    /**
     * Create a new appointment
     *
     * @param CreateAppointmentRequest $request
     * @param Company $company
     * @return AppointmentResource|JsonResponse
     */
    public function store(CreateAppointmentRequest $request, Company $company)
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
     *
     * @param Company $company
     * @param Appointment $appointment
     * @return AppointmentResource
     */
    public function show(Company $company, Appointment $appointment): AppointmentResource
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
     * @param UpdateAppointmentRequest $request
     * @param Company $company
     * @param Appointment $appointment
     * @return AppointmentResource|JsonResponse
     */
    public function update(UpdateAppointmentRequest $request, Company $company, Appointment $appointment)
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
     *
     * @param Company $company
     * @param Appointment $appointment
     * @return JsonResponse
     */
    public function cancel(Company $company, Appointment $appointment): JsonResponse
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
     *
     * @param Company $company
     * @param Appointment $appointment
     * @return JsonResponse
     */
    public function destroy(Company $company, Appointment $appointment): JsonResponse
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
