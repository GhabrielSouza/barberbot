<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CommissionMethod — catálogo dos tipos de divisão de comissão (schema
 * public, seed fixo). Só para a UI listar as opções; a lógica de cálculo
 * fica no backend (commission_settings/commission_shares, no schema do tenant).
 *
 * code: per_attendance | equal_split | custom_percent
 */
class CommissionMethod extends Model
{
    use HasUuids;

    protected $table = 'commission_methods';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];
}
