<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotResponse extends Model
{
    protected $fillable = [
        'keyword',
        'response',
        'match_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
