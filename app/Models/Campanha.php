<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'import_id',
        'name',
        'info',
        'type',
        'color', 
        'lat',
        'lng',
        'total_liquido'
    ];

        // Relacionamento: Uma campanha pertence a um upload
        public function import()
        {
            return $this->belongsTo(import::class);
        }
}