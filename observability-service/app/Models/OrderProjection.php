<?php

namespace App\Models;

use Database\Factories\OrderProjectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProjection extends Model
{
    /** @use HasFactory<OrderProjectionFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'aggregate_version',
        'restaurant_id',
        'client_id',
        'rider_id',
        'last_event_type',
        'last_event_at',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_version' => 'integer',
            'last_event_at' => 'datetime',
        ];
    }
}
