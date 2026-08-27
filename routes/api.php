<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\CommissionMethodController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

// Public Routes
Route::prefix('webhook')->group(function () {
    Route::post('whatsapp', [WhatsAppController::class, 'webhook']);
    Route::get('test', [WhatsAppController::class, 'test']);

    
});

Route::get('status', function () {
    $now = Carbon::now();

    return response()->json([
        'status' => 'online',
        'server_time' => $now->toTimeString(), // Apenas a hora (ex: 14:30:00)
        'server_date' => $now->toDateString(), // Apenas a data (ex: 2026-06-07)
        'timestamp' => $now->toIso8601String(), // Opcional: data e hora completas
    ]);
});

// Authentication routes for SPA/API (no CSRF required)
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('api.login');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('api.register');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('api.logout');

    Route::get('/user', function (Request $request) {
        return $request->user()->load('tenant');
    })
        ->middleware('auth')
        ->name('api.user');
});

// Commission Methods Routes (catálogo global, schema public)
Route::middleware(['api'])->prefix('commission-methods')->group(function () {
    Route::get('/', [CommissionMethodController::class, 'index']);
    Route::post('/', [CommissionMethodController::class, 'store']);
    Route::get('{commission_method}', [CommissionMethodController::class, 'show']);
    Route::put('{commission_method}', [CommissionMethodController::class, 'update']);
    Route::delete('{commission_method}', [CommissionMethodController::class, 'destroy']);
});

// Tenants Routes (catálogo global, schema public)
Route::middleware(['api'])->prefix('tenants')->group(function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::get('{tenant}', [TenantController::class, 'show']);
    Route::put('{tenant}', [TenantController::class, 'update']);
    Route::patch('{tenant}/cancel', [TenantController::class, 'cancel']);
    Route::delete('{tenant}', [TenantController::class, 'destroy']);
});

// Plans Routes (catálogo global de planos, schema public)
Route::middleware(['api'])->prefix('plans')->group(function () {
    Route::get('/', [PlanController::class, 'index']);
    Route::post('/', [PlanController::class, 'store']);
    Route::get('active', [PlanController::class, 'active']);
    Route::get('{plan}', [PlanController::class, 'show']);
    Route::put('{plan}', [PlanController::class, 'update']);
    Route::patch('{plan}/toggle', [PlanController::class, 'toggleActive']);
    Route::delete('{plan}', [PlanController::class, 'destroy']);
});

// Tenant Users Routes (logins globais do tenant)
Route::middleware(['api'])->prefix('tenants/{tenant}')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('{user}', [UserController::class, 'show']);
        Route::put('{user}', [UserController::class, 'update']);
        Route::delete('{user}', [UserController::class, 'destroy']);
    });
});

// Routes that require authentication
Route::middleware(['api', 'auth'])->group(function () {
    // Dashboard Routes
    Route::prefix('companies/{company}')->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('metrics', [DashboardController::class, 'metrics']);
            Route::get('appointments', [DashboardController::class, 'appointmentsByDateRange']);
            Route::get('revenue', [DashboardController::class, 'revenue']);
            Route::get('top-services', [DashboardController::class, 'topServices']);
            Route::get('barbers-performance', [DashboardController::class, 'barberPerformance']);
            Route::get('clients-stats', [DashboardController::class, 'clientStats']);
        });

        // Appointments Routes
        Route::prefix('appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index']);
            Route::post('/', [AppointmentController::class, 'store']);
            Route::get('today', [AppointmentController::class, 'today']);
            Route::get('{appointment}', [AppointmentController::class, 'show']);
            Route::put('{appointment}', [AppointmentController::class, 'update']);
            Route::patch('{appointment}/cancel', [AppointmentController::class, 'cancel']);
            Route::delete('{appointment}', [AppointmentController::class, 'destroy']);
        });

        // Barbers Routes
        Route::prefix('barbers')->group(function () {
            Route::get('/', [BarberController::class, 'index']);
            Route::post('/', [BarberController::class, 'store']);
            Route::get('active', [BarberController::class, 'active']);
            Route::get('{barber}', [BarberController::class, 'show']);
            Route::put('{barber}', [BarberController::class, 'update']);
            Route::patch('{barber}/toggle', [BarberController::class, 'toggleActive']);
            Route::delete('{barber}', [BarberController::class, 'destroy']);

            // Schedules nested under Barbers
            Route::prefix('{barber}/schedules')->group(function () {
                Route::get('/', [ScheduleController::class, 'indexForBarber']);
                Route::post('/', [ScheduleController::class, 'store']);
                Route::get('day/{day}', [ScheduleController::class, 'byDay']);
                Route::post('bulk', [ScheduleController::class, 'bulkCreate']);
                Route::get('{schedule}', [ScheduleController::class, 'show']);
                Route::put('{schedule}', [ScheduleController::class, 'update']);
                Route::delete('{schedule}', [ScheduleController::class, 'destroy']);
            });
        });

        // Services Routes
        Route::prefix('services')->group(function () {
            Route::get('/', [ServiceController::class, 'index']);
            Route::post('/', [ServiceController::class, 'store']);
            Route::get('active', [ServiceController::class, 'active']);
            Route::get('{service}', [ServiceController::class, 'show']);
            Route::put('{service}', [ServiceController::class, 'update']);
            Route::patch('{service}/toggle', [ServiceController::class, 'toggleActive']);
            Route::delete('{service}', [ServiceController::class, 'destroy']);
        });

        // Products Routes
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('active', [ProductController::class, 'active']);
            Route::get('{product}', [ProductController::class, 'show']);
            Route::put('{product}', [ProductController::class, 'update']);
            Route::patch('{product}/toggle', [ProductController::class, 'toggleActive']);
            Route::delete('{product}', [ProductController::class, 'destroy']);
        });
    });
});



