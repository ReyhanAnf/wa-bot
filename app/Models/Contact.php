<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'wa_number',
        'name',
        'nickname',
        'role',
        'personal_notes',
    ];
}
