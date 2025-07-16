<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'name',
        'info',
        'type',
        'color', 
        'lat',
        'lng'
    ];
}