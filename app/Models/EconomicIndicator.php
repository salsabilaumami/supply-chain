<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicIndicator extends Model
{
    protected $fillable = [
        'country_id',
        'indicator_code',
        'indicator_name',
        'year',
        'value',
        'source',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'value' => 'decimal:4',
            'fetched_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}