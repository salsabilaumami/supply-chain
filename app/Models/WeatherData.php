<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends Model
{
    protected $fillable = [
        'country_id',
        'temperature',
        'precipitation',
        'wind_speed',
        'weather_code',
        'weather_risk',
        'recorded_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'precipitation' => 'decimal:2',
            'wind_speed' => 'decimal:2',
            'weather_code' => 'integer',
            'weather_risk' => 'decimal:2',
            'recorded_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}