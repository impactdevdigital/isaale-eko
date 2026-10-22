<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreHour extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weekday' => 'integer',
        'is_closed' => 'boolean',
    ];

    public function weekdayName(): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$this->weekday] ?? 'Unknown';
    }
}
