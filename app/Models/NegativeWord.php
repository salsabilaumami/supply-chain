<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NegativeWord extends Model
{
    protected $fillable = [
        'word',
        'language',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }
}