<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'step',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public $timestamps = true;

    /**
     * Get the user for this conversation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get conversations in a specific step
     */
    public function scopeInStep($query, string $step)
    {
        return $query->where('step', $step);
    }

    /**
     * Get data attribute with empty array as default
     */
    public function getDataAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }
}
