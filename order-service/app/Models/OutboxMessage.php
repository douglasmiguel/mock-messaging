<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboxMessage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'event_type',
        'event_version',
        'payload',
        'occurred_at',
        'published_at',
        'publish_attempts',
        'last_publish_error',
        'last_publish_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_version' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'published_at' => 'datetime',
            'publish_attempts' => 'integer',
            'last_publish_failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
