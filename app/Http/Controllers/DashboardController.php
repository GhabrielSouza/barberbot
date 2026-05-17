<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Get dashboard metrics
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function metrics(Company $company): JsonResponse
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // Today's appointments
        $todayAppointments = Appointment::where('company_id', $company->id)
            ->where('date', $today->format('Y-m-d'))
            ->count();

        // Pending appointments
        $pendingAppointments = Appointment::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // Confirmed appointments
        $confirmedAppointments = Appointment::where('company_id', $company->id)
            ->where('status', 'confirmed')
            ->count();

        // Total clients
        $totalClients = User::where('company_id', $company->id)->count();

        // Total revenue (this month)
        $monthlyRevenue = $this->appointmentService->getRevenueByDateRange($company->id, $startOfMonth, $endOfMonth);

        // Daily revenue (today)
        $todayRevenue = Appointment::where('company_id', $company->id)
            ->where('date', $today->format('Y-m-d'))
            ->where('status', 'completed')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');

        // Total barbers
        $totalBarbers = Barber::where('company_id', $company->id)
            ->where('active', true)
            ->count();

        // Total services
        $totalServices = Service::where('company_id', $company->id)
            ->where('active', true)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today_appointments' => $todayAppointments,
                'pending_appointments' => $pendingAppointments,
                'confirmed_appointments' => $confirmedAppointments,
                'total_clients' => $totalClients,
                'total_barbers' => $totalBarbers,
                'total_services' => $totalServices,
                'monthly_revenue' => floatval($monthlyRevenue),
                'today_revenue' => floatval($todayRevenue),
            ],
        ]);
    }

    /**
     * Get appointments for a date range
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function appointmentsByDateRange(Company $company): JsonResponse
    {
        $request = request();
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $appointments = $this->appointmentService->getAppointmentsByDateRange($company->id, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $appointments->count(),
                'appointments' => $appointments->map(fn ($apt) => [
                    'id' => $apt->id,
                    'date' => $apt->date->format('Y-m-d'),
                    'time' => $apt->time,
                    'client' => $apt->user->name,
                    'barber' => $apt->barber->name,
                    'service' => $apt->service->name,
                    'price' => $apt->service->price,
                    'status' => $apt->status,
                ])->toArray(),
            ],
        ]);
    }

    /**
     * Get revenue by date range
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function revenue(Company $company): JsonResponse
    {
        $request = request();
        $startDate = Carbon::parse($request->input('start_date', Carbon::today()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', Carbon::today()->endOfMonth()));

        $revenue = $this->appointmentService->getRevenueByDateRange($company->id, $startDate, $endDate);

        // Get daily breakdown
        $dailyRevenue = Appointment::where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('date')
            ->selectRaw('SUM(services.price) as total')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->groupBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => floatval($revenue),
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'daily_breakdown' => $dailyRevenue->map(fn ($item) => [
                    'date' => $item->date,
                    'total' => floatval($item->total),
                ])->toArray(),
            ],
        ]);
    }

    /**
     * Get most used services
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function topServices(Company $company): JsonResponse
    {
        $services = $this->appointmentService->getMostUsedServices($company->id, 5);

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * Get barber performance
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function barberPerformance(Company $company): JsonResponse
    {
        $barbers = Barber::where('company_id', $company->id)
            ->where('active', true)
            ->get();

        $performance = $barbers->map(function ($barber) {
            $appointments = Appointment::where('barber_id', $barber->id)
                ->where('status', 'completed')
                ->count();

            $revenue = Appointment::where('barber_id', $barber->id)
                ->where('status', 'completed')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->sum('services.price');

            return [
                'id' => $barber->id,
                'name' => $barber->name,
                'total_appointments' => $appointments,
                'total_revenue' => floatval($revenue),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $performance->toArray(),
        ]);
    }

    /**
     * Get client statistics
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function clientStats(Company $company): JsonResponse
    {
        $totalClients = User::where('company_id', $company->id)->count();
        $activeClients = User::where('company_id', $company->id)
            ->whereHas('appointments')
            ->distinct('id')
            ->count();

        $userAppointments = User::where('company_id', $company->id)
            ->withCount('appointments')
            ->orderBy('appointments_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'top_clients' => $userAppointments->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'total_appointments' => $user->appointments_count,
                ])->toArray(),
            ],
        ]);
    }
}
