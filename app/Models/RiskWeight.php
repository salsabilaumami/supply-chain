<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskWeight extends Model
{
    protected $fillable = [
        'component_name',
        'weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}