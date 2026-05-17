<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'direction',
        'message',
    ];

    /**
     * Get the user for this message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get incoming messages
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'in');
    }

    /**
     * Scope: Get outgoing messages
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'out');
    }

    /**
     * Scope: Get messages for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get recent messages
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
