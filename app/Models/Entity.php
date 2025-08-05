<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;

    // Nome da tabela no banco de dados
    protected $table = 'entities';

    // Campos que podem ser preenchidos em massa (mass assignment)
    protected $fillable = [
        'name',
        'razao_social',
        'type',
        'cnpj_cpf',
        'codigo_tabela',
        'codigo',
        'praca',
        'rede',
        'address_id',
    ];

    // Relacionamento com o modelo Address
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    // Atributos que devem ser tratados como datas
    protected $dates = ['created_at', 'updated_at'];
}