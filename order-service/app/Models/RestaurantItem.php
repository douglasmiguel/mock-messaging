<?php

namespace App\Models;

use Database\Factories\RestaurantItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantItem extends Model
{
    /** @use HasFactory<RestaurantItemFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'item_category_id',
        'name',
        'item_price',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'item_price' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
