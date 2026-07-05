<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'country_id',
        'base_currency',
        'target_currency',
        'rate',
        'change_percentage',
        'currency_risk',
        'recorded_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'change_percentage' => 'decimal:4',
            'currency_risk' => 'decimal:2',
            'recorded_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}