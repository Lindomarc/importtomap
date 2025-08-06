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

    // Método para acessar o tipo formatado
    public function getTypeFormattedAttribute()
    {
        $types = [
            'OH' => 'Outdoor',
            'IN' => 'Internet',
            'RD' => 'Rádio',
            'JN' => 'Jornal',
            'TV' => 'Televisão',
            'RE' => 'Revista',
            'GR' => 'Gráfica',
            'OU' => 'Outros',
        ];

        return $types[$this->type] ?? 'Desconhecido';
    }

    // Método para acessar o CNPJ/CPF formatado
    public function getCnpjCpfFormattedAttribute()
    {
        if (strlen($this->cnpj_cpf) === 14) {
            // Formata como CNPJ
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $this->cnpj_cpf);
        } elseif (strlen($this->cnpj_cpf) === 11) {
            // Formata como CPF
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $this->cnpj_cpf);
        }

        return $this->cnpj_cpf;
    }
}