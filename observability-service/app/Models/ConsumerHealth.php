<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerHealth extends Model
{
    protected $table = 'consumer_health';

    protected $fillable = ['service', 'last_success_at', 'last_failure_at', 'last_error'];

    protected function casts(): array
    {
        return [
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }
}
