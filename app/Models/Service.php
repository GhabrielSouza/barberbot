<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'price',
        'duration_min',
        'duration_minutes',
        'category',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_min' => 'integer',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $service) {
            $service->id ??= (string) Str::uuid();
        });
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) ($this->duration_min ?? 0);
    }

    public function setDurationMinutesAttribute($value): void
    {
        $this->attributes['duration_min'] = $value;
    }

    /**
     * Get the company that owns this service
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all appointments for this service
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
