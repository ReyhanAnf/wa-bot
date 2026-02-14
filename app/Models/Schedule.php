<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'day',
        'subject',
        'start_time',
        'end_time',
        'lecturer',
        'room',
        'is_active',
        'student_name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // 'start_time' => 'datetime:H:i', // Optional, depends on usage
        // 'end_time' => 'datetime:H:i',
    ];
}
