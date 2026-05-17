<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'barber_id',
        'service_id',
        'company_id',
        'date',
        'time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user (client) for this appointment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the barber for this appointment
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    /**
     * Get the service for this appointment
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the company for this appointment
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: Get confirmed appointments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope: Get appointments for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope: Get appointments for a specific barber
     */
    public function scopeForBarber($query, $barberId)
    {
        return $query->where('barber_id', $barberId);
    }

    /**
     * Scope: Get appointments for today
     */
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }
}
