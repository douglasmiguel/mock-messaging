<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSnapshot extends Model
{
    protected $fillable = ['order_id', 'status', 'aggregate_version', 'restaurant_name', 'restaurant_email', 'client_name', 'client_email', 'payload'];

    protected function casts(): array
    {
        return ['aggregate_version' => 'integer', 'payload' => 'array'];
    }
}
