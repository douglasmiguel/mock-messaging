<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderAssignment extends Model
{
    protected $fillable = ['assignment_id', 'order_id', 'rider_id', 'status'];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }
}
