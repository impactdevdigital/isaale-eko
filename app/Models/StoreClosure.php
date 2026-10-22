<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreClosure extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];
}
