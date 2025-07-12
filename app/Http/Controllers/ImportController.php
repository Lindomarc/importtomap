<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Campanha;
use App\Services\CityService;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{

    private $cidadeService;
    
    public function __construct(CityService  $cidadeService)
    {
        $this->cidadeService = $cidadeService;
    }
    public function index()
    {
        return view('imports.upload'); // Exibe o formulário de upload
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();
            unset($rows[0]); // Remove cabeçalho

            $sucessos = 0;
            $erros = [];

            foreach ($rows as $index => $row) {
                try {
                    $cidadeId = null;
                    $data = [
                        'campanha' => $row[0] ?? null,
                        'cliente' => $row[1] ?? null,
                        'veiculo' => $row[2] ?? null,
                        'meio' => $row[3] ?? null,
                        'praca' => $row[4] ?? null,
                    ];
                    // Processa o campo 'meio' para extrair cidade
                    if (!empty($row[4])) {
                        $cidade = $this->cidadeService->findOrCreateCity($row[4]);
                        $cidadeId = $cidade ? $cidade->id : null;
                        $data['lat'] = $cidade ? $cidade->lat : null;
                        $data['lng'] = $cidade ? $cidade->lng : null;
                    }
                    
                    Campanha::create($data);
                    
                    $sucessos++;
                    
                } catch (\Exception $e) {
                    $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Importação concluída. {$sucessos} campanhas importadas.";
            if (!empty($erros)) {
                $message .= " Erros: " . implode(', ', $erros);
            }

            return response()->json(['message' => $message], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao importar planilha: ' . $e->getMessage()
            ], 500);
        }
    }
}