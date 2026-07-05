<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsSentiment extends Model
{
    protected $fillable = [
        'news_cache_id',
        'positive_score',
        'negative_score',
        'neutral_score',
        'sentiment',
        'risk_score',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'positive_score' => 'integer',
            'negative_score' => 'integer',
            'neutral_score' => 'integer',
            'risk_score' => 'decimal:2',
            'analyzed_at' => 'datetime',
        ];
    }

    public function newsCache(): BelongsTo
    {
        return $this->belongsTo(NewsCache::class);
    }
}