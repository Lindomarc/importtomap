<?php

namespace App\Imports;

use App\Models\Campanha;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CampanhasImport implements ToModel, WithHeadingRow
{
    /**
     * Transforma uma linha da planilha em um modelo Eloquent.
     */
    public function model(array $row)
    {
        return new Campanha([
            'campanha' => $row['campanha'] ?? null,
            'cliente' => $row['cliente'] ?? null,
            'veiculo' => $row['veiculo'] ?? null,
            'meio' => $row['meio'] ?? null,
            'praca' => $row['praca'] ?? null,
        ]);
    }
}