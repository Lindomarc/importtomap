<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'date',
        'type',
        'file_size',
        'success_count',
        'error_count',
        'errors'
    ];

    protected $casts = [
        'date' => 'date',
        'errors' => 'array',
        'success_count' => 'integer',
        'error_count' => 'integer'
    ];

    // Scopes para filtrar por tipo
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessor para status da importação
    public function getStatusAttribute()
    {
        if ($this->error_count === 0) {
            return 'sucesso';
        } elseif ($this->success_count === 0) {
            return 'falha';
        } else {
            return 'parcial';
        }
    }

    // Accessor para total de registros processados
    public function getTotalRecordsAttribute()
    {
        return $this->success_count + $this->error_count;
    }

    // Relacionamento: Um upload pode ter várias campanhas
    public function campanhas()
    {
        return $this->hasMany(Campanha::class);
    }
}