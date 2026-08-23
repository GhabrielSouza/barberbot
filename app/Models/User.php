<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User — login global (schema public), usado para autenticar antes de
 * resolver o tenant. Não confundir com dados de negócio (Client, TeamMember,
 * ...) que vivem no schema do tenant (conexão "tenant").
 *
 * role: owner | supervisor | atendente
 */
class User extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password_hash',
        'role',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Laravel's auth guard expects getAuthPassword(); a coluna aqui é
     * `password_hash`, não `password`.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
