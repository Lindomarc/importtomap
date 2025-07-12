<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'name',
        'state',
        'lat',
        'lng'
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8'
    ];
}
