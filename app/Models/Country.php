<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name',
        'official_name',
        'iso2_code',
        'iso3_code',
        'capital',
        'region',
        'subregion',
        'latitude',
        'longitude',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'population',
        'flag_url',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'population' => 'integer',
        ];
    }

    public function economicIndicators(): HasMany
    {
        return $this->hasMany(EconomicIndicator::class);
    }

    public function weatherData(): HasMany
    {
        return $this->hasMany(WeatherData::class);
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function newsCaches(): HasMany
    {
        return $this->hasMany(NewsCache::class);
    }

    public function ports(): HasMany
    {
        return $this->hasMany(Port::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(RiskScore::class);
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }
}