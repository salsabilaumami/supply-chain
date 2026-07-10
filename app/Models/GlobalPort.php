<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalPort extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'city',
        'type',
        'latitude',
        'longitude',
        'capacity_score',
        'congestion_score',
        'weather_exposure_score',
        'risk_score',
        'risk_level',
        'description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity_score' => 'float',
        'congestion_score' => 'float',
        'weather_exposure_score' => 'float',
        'risk_score' => 'float',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}