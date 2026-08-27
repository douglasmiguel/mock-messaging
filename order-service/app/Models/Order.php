<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_name',
        'client_phone',
        'client_email',
        'delivery_address',
        'restaurant_id',
        'order_price',
        'delivery_fee',
        'total_price',
        'rider_id',
        'status',
        'aggregate_version',
    ];

    protected function casts(): array
    {
        return [
            'order_price' => 'integer',
            'delivery_fee' => 'integer',
            'total_price' => 'integer',
            'status' => OrderStatus::class,
            'aggregate_version' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function outboxMessages(): HasMany
    {
        return $this->hasMany(OutboxMessage::class);
    }
}
