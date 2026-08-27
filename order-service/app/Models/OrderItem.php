<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_item_id',
        'item_name',
        'item_price',
        'quantity',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'item_price' => 'integer',
            'quantity' => 'integer',
            'total_price' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurantItem(): BelongsTo
    {
        return $this->belongsTo(RestaurantItem::class);
    }
}
