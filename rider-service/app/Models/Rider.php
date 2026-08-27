<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'vehicle_name', 'license'];

    public function assignments(): HasMany
    {
        return $this->hasMany(RiderAssignment::class);
    }
}
