<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskComponent extends Model
{
    protected $fillable = [
        'risk_score_id',
        'component_name',
        'raw_value',
        'normalized_score',
        'weight',
        'weighted_score',
    ];

    protected function casts(): array
    {
        return [
            'raw_value' => 'decimal:4',
            'normalized_score' => 'decimal:2',
            'weight' => 'decimal:2',
            'weighted_score' => 'decimal:2',
        ];
    }

    public function riskScore(): BelongsTo
    {
        return $this->belongsTo(RiskScore::class);
    }
}