<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerIncident extends Model
{
    protected $fillable = [
        'event_id',
        'service',
        'outcome',
        'source_event_id',
        'source_event_type',
        'order_id',
        'retry_count',
        'error',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
