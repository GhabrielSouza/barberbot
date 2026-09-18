<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $connection = 'tenant';

    protected $fillable = [
        'client_id', 'team_member_id', 'service_id', 'service_name', 'price',
        'date', 'start_time', 'end_time', 'status', 'payment_method',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class, 'team_member_id');
    }

    /**
     * Get the service for this appointment
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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
        return $query->where('team_member_id', $barberId);
    }

    /**
     * Scope: Get appointments for today
     */
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }
}
