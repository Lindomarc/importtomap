<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'state',
        'city',
        'street',
        'cep',
        'lat',
        'lng'
    ];

    protected $casts = [
        'lat' => 'decimal:11',
        'lng' => 'decimal:11'
    ];
}
