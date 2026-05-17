<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'company_id',
    ];

    /**
     * Get the company that owns this user
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all appointments for this user
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get conversation for this user
     */
    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * Get all messages for this user
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
