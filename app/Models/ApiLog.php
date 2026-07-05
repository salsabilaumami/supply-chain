<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'api_name',
        'endpoint',
        'status_code',
        'response_time',
        'status',
        'error_message',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time' => 'integer',
            'requested_at' => 'datetime',
        ];
    }
}