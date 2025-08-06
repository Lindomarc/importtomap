<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Campanha;
use App\Services\AddressService;
use Illuminate\Support\Facades\Log;

class ImportInertiaController extends Controller
{

    private $cidadeService;
    
    public function __construct(AddressService  $cidadeService)
    {
        $this->cidadeService = $cidadeService;
    }
    public function index()
    {
        return view('imports.index');
    
    }

    public function upload()
    {
        return view('imports.upload'); // Exibe o formulário de upload
    }

    public function import(Request $request)
    {

        $type = $request->input('type');
        // Log::info('info', [$type]);
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        // try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();
            if($type ==='IN'){
                unset($rows[0],$rows[1]); // Remove cabeçalho
            }
           
            $sucessos = 0;
            $erros = [];
            $colors = [
                'RD' => 'green',
                'OH' => 'red',
                'IN' => 'blue'
            ];

            foreach ($rows as $index => $row) {
                try {
                    $data = [
                        'name' => $row[0] ?? null,
                        'info' => $row[1] ?? null,
                        'city' => $row[2] ?? null,
                        'state' => $row[3] ?? null,
                        'street' => $row[4] ?? null,
                        'number' => $row[5] ?? null,
                        'cep' => $row[6] ?? null,
                    ];

                    // Processa o campo 'meio' para extrair cidade
                    $address = $this->cidadeService->findOrCreateAddress($data);
                    $data['address_id'] = $address ? $address->id : null;
                    $data['lat'] = $address ? $address->lat : null;
                    $data['lng'] = $address ? $address->lng : null;
                    $data['type'] = $request->input('type');
                    $data['color'] = $colors[$data['type']];
                    if($data['name']  && $data['info']){
                        Campanha::create($data);
                        $sucessos++;
                    
                    }
                    
                    
                } catch (\Exception $e) {
                    $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
                    Log::info("errors",[$erros]);
                }
            }

            $message = "Importação concluída. {$sucessos} campanhas importadas.";
            if (!empty($erros)) {
                $message .= " Erros: " . implode(', ', $erros);
            }

            return response()->json(['message' => $message], 200);
            
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error' => 'Erro ao importar planilha: ' . $e->getMessage()
        //     ], 500);
        // }
    }
}