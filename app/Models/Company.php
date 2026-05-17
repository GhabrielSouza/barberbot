<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp_instance',
        'whatsapp_token',
    ];

    /**
     * Get all users (clients) for this company
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all barbers for this company
     */
    public function barbers(): HasMany
    {
        return $this->hasMany(Barber::class);
    }

    /**
     * Get all services for this company
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all appointments for this company
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
