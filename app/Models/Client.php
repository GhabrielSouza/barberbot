<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasUuids;

    protected $connection = 'tenant';

    const UPDATED_AT = null;

    protected $fillable = ['name', 'phone', 'tag', 'notes', 'rating', 'color'];

    protected $casts = ['rating' => 'decimal:1'];
}
