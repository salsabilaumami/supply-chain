<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
            'country_id' => 'integer',
            'year' => 'integer',
            'value' => 'decimal:4',
            'fetched_at' => 'datetime',
        ];
    }

    public function scopeForIndicator(
        Builder $query,
        string $indicatorCode
    ): Builder {
        return $query->where(
            'indicator_code',
            strtoupper(trim($indicatorCode))
        );
    }

    public function scopeLatestAvailable(Builder $query): Builder
    {
        return $query
            ->orderByDesc('year')
            ->orderByDesc('fetched_at');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}