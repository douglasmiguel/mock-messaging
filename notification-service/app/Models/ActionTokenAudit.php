<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionTokenAudit extends Model
{
    protected $fillable = ['action_token_id', 'order_id', 'actor', 'action', 'succeeded', 'request_fingerprint'];

    protected function casts(): array
    {
        return ['succeeded' => 'boolean'];
    }
}
