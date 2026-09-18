<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function table(string $table): Builder
    {
        return DB::connection('tenant')->table($table);
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function period(): array
    {
        $data = request()->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        return [
            $data['start_date'] ?? Carbon::today()->startOfMonth()->toDateString(),
            $data['end_date'] ?? Carbon::today()->endOfMonth()->toDateString(),
        ];
    }

    public function metrics(Tenant $company): JsonResponse
    {
        $today = Carbon::today();
        $appointments = $this->table('appointments');

        return $this->result([
            'today_appointments' => (clone $appointments)->whereDate('date', $today)->count(),
            'pending_appointments' => (clone $appointments)->where('status', 'pending')->count(),
            'confirmed_appointments' => (clone $appointments)->where('status', 'confirmed')->count(),
            'total_clients' => $this->table('clients')->count(),
            'total_barbers' => $this->table('team_members')->where('active', true)->count(),
            'total_services' => $this->table('services')->where('active', true)->count(),
            // Use the price recorded at booking, not the current service price.
            'monthly_revenue' => (float) (clone $appointments)->where('status', 'done')
                ->whereBetween('date', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])->sum('price'),
            'today_revenue' => (float) (clone $appointments)->where('status', 'done')->whereDate('date', $today)->sum('price'),
        ]);
    }

    public function appointmentsByDateRange(Tenant $company): JsonResponse
    {
        $appointments = $this->table('appointments as a')
            ->leftJoin('clients as c', 'a.client_id', '=', 'c.id')
            ->leftJoin('team_members as b', 'a.team_member_id', '=', 'b.id')
            ->whereBetween('a.date', $this->period())
            ->orderBy('a.date')->orderBy('a.start_time')
            ->get(['a.id', 'a.date', 'a.start_time as time', 'c.name as client', 'b.name as barber', 'a.service_name as service', 'a.price', 'a.status']);

        return $this->result(['total' => $appointments->count(), 'appointments' => $appointments->all()]);
    }

    public function revenue(Tenant $company): JsonResponse
    {
        [$start, $end] = $this->period();
        $query = $this->table('appointments')->where('status', 'done')->whereBetween('date', [$start, $end]);
        $total = (float) (clone $query)->sum('price');
        $daily = $query->select('date')->selectRaw('SUM(price) as total')->groupBy('date')->orderBy('date')->get();

        return $this->result([
            'total_revenue' => $total,
            'period' => ['start' => $start, 'end' => $end],
            'daily_breakdown' => $daily->map(fn ($row) => ['date' => $row->date, 'total' => (float) $row->total])->all(),
        ]);
    }

    public function topServices(Tenant $company): JsonResponse
    {
        $services = $this->table('appointments as a')->leftJoin('services as s', 'a.service_id', '=', 's.id')
            ->where('a.status', 'done')->select('a.service_id')->selectRaw('MAX(s.name) as service_name, COUNT(*) as count')
            ->groupBy('a.service_id')->orderByDesc('count')->limit(5)->get();

        return $this->result($services->all());
    }

    public function barberPerformance(Tenant $company): JsonResponse
    {
        $barbers = $this->table('team_members as b')->leftJoin('appointments as a', function ($join) {
            $join->on('a.team_member_id', '=', 'b.id')->where('a.status', 'done');
        })->where('b.active', true)->select('b.id', 'b.name')
            ->selectRaw('COUNT(a.id) as total_appointments, COALESCE(SUM(a.price), 0) as total_revenue')
            ->groupBy('b.id', 'b.name')->orderBy('b.name')->get();

        return $this->result($barbers->map(fn ($row) => [
            'id' => $row->id, 'name' => $row->name,
            'total_appointments' => (int) $row->total_appointments,
            'total_revenue' => (float) $row->total_revenue,
        ])->all());
    }

    public function clientStats(Tenant $company): JsonResponse
    {
        $top = $this->table('clients as c')->leftJoin('appointments as a', 'a.client_id', '=', 'c.id')
            ->select('c.id', 'c.name', 'c.phone')->selectRaw('COUNT(a.id) as total_appointments')
            ->groupBy('c.id', 'c.name', 'c.phone')->orderByDesc('total_appointments')->limit(5)->get();

        return $this->result([
            'total_clients' => $this->table('clients')->count(),
            'active_clients' => $this->table('clients')->whereIn('id', $this->table('appointments')->select('client_id'))->count(),
            'top_clients' => $top->all(),
        ]);
    }
}
