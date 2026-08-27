<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';
    protected $table = 'team_members';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'role',
        'color',
        'is_admin',
        'user_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /**
     * Get the bound user account for this team member, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all schedules for this barber.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'barber_id');
    }

    /**
     * Get all appointments for this barber.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'team_member_id');
    }
}
