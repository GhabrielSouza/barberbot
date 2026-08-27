<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Plan — catálogo de planos do SaaS (schema public).
 *
 * Cobrança fixo + por membro extra:
 *   total = price_month + max(0, membros_ativos - included_members) * price_per_extra_member
 */
class Plan extends Model
{
    use HasUuids;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'price_month',
        'included_members',
        'price_per_extra_member',
        'limits',
        'active',
    ];

    protected $casts = [
        'price_month' => 'decimal:2',
        'included_members' => 'integer',
        'price_per_extra_member' => 'decimal:2',
        'limits' => 'array',
        'active' => 'boolean',
    ];
}
