<?php

namespace App\Models;

use Database\Factories\ObservedEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObservedEvent extends Model
{
    /** @use HasFactory<ObservedEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'event_version',
        'order_id',
        'payload',
        'occurred_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'event_version' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }
}
