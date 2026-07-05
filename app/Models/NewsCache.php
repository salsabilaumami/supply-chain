<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NewsCache extends Model
{
    protected $fillable = [
        'country_id',
        'title',
        'description',
        'url',
        'source_name',
        'author',
        'image_url',
        'published_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function sentiment(): HasOne
    {
        return $this->hasOne(NewsSentiment::class);
    }
}