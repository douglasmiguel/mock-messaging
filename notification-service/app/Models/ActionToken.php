<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionToken extends Model
{
    protected $fillable = ['order_id', 'actor', 'token_hash', 'expires_at', 'revoked_at', 'last_used_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
