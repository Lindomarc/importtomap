<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Campanha;
use App\Models\Upload;
use App\Models\Import;
use App\Services\AddressService;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{

    private $cidadeService;
    
    public function __construct(AddressService  $cidadeService)
    {
        $this->cidadeService = $cidadeService;
    }

    public function index()
    {
        $imports = Import::orderBy('created_at', 'desc')->paginate(15);
        return response()->json( $imports, 200);

    }

    public function upload()
    {
        return view('imports.upload'); // Exibe o formulário de upload
    }

    public function store(Request $request)
    {
        // Validação do arquivo
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'type' => 'required|string', // Garante que o tipo seja informado
        ]);
    
        $type = $request->input('type');
        
        // try {
            // Obtém o arquivo enviado
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
    
            // Carrega o arquivo usando PhpSpreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Define o número de linhas de cabeçalho para cada tipo
            $headerLinesToRemove = [
                'portais'   => 2, // Remove 2 linhas para 'portais'
                'emissoras' => 6, // Remove 6 linhas para 'emissoras'
                'placas'    => 3, // Remove 3 linhas para 'placas'
                'entidades' => 1
            ];

            // Verifica se o tipo existe no mapa de configuração
            if (isset($headerLinesToRemove[$type])) {
                $rows = array_slice($rows, $headerLinesToRemove[$type]); // Remove as linhas de cabeçalho
            }
            
            // Variáveis para controle de sucesso e erros
            $sucessos = 0;
            $erros = [];
            $colors = [
                'emissoras' => 'green',
                'placas' => 'red',
                'portais' => 'blue',
                'entidades' => 'black'
            ];

            // Registra importação com falha
            $import = Import::create([
                'name' => $fileName ?? 'Arquivo desconhecido',
                'date' => now()->toDateString(),
                'file_size' => $fileSize,
                'type' => $request->input('type') ?? 'desconhecido',
                'success_count' => 0,
                'error_count' => 0,
                'errors' => 0,
            ]);
        // }   catch (\Exception $e) {
                
        //         Log::debug("Erro ao importar planilha: ",[$e->getMessage()]);
        //         return response()->json([
        //             'error' => 'Erro ao importar planilha: ' . $e->getMessage()
        //         ], 500);
                
        //     }
        // try {
            // Processa cada linha do arquivo
            if($import){
                
                foreach ($rows as $index => $row) {
                    try {
                        $data = $this->validate($request, $row);
                
                        // Processa o campo 'meio' para extrair cidade
                        $address = $this->cidadeService->findOrCreateAddress($data);
                        $data['address_id'] = $address ? $address->id : null;
                        $data['lat'] = $address ? $address->lat : null;
                        $data['lng'] = $address ? $address->lng : null;
                        $data['color'] = $colors[$type] ?? null;
        
                        // Associa o upload ao registro da campanha
                        $data['import_id'] = $import->id;
        
                        // Cria a campanha se os campos obrigatórios estiverem presentes
                        if ($data['name'] && $data['info']) {
                            $campanha = Campanha::create($data);
                            $sucessos++;
                        }
                    } catch (\Exception $e) {
                        $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
                    }
                }
        
                // Mensagem de sucesso
                $message = "Importação concluída. {$sucessos} campanhas importadas.";
                if (!empty($erros)) {
                    Log::info("import store",[$erros]);
                }
        
                return response()->json(['message' => $message], 200);
        
            }
        // } catch (\Exception $e) {
        //     // Registra importação com falha
        //     Import::create([
        //         'name' => $fileName ?? 'Arquivo desconhecido',
        //         'date' => now()->toDateString(),
        //         'type' => $request->input('type') ?? 'desconhecido',
        //         'success_count' => 0,
        //         'error_count' => 1,
        //         'errors' => ['Erro geral: ' . $e->getMessage()]
        //     ]);
        //     Log::debug("Erro ao importar planilha: ",[$e->getMessage()]);
        //     return response()->json([
        //         'error' => 'Erro ao importar planilha: ' . $e->getMessage()
        //     ], 500);
            
        // }
    }

    private function validate($request, $data)
    {
        $type = $request->input('type');
        $state = $request->input('state') ?? 'PR';
        $street = $this->normalizeString($data[6] ?? null);
        if ($type === 'portais') {
            if (!!$data[0] && !!$data[3] && !!$data[4]) {
                $total = $data[11] ? $this->formatarMoeda($data[11]) : 0;
                return [
                    'name' => $this->normalizeString($data[0] ?? null),
                    'info' => $this->normalizeString($data[3] ?? null),
                    'city' => $this->normalizeString($data[2] ?? null),
                    'type' => 'portais',
                    'state' => $state,
                    'street' => $street,
                    'number' => null,
                    'total_liquido' => $total,
                    'cep' => null,
                ];
            }
            return [];
        }

        if ($type === 'emissoras') {
            if (!!$data[0] && !!$data[1] && !!$data[3] && !!$data[4] && !!$data[39]) {
                $total = $data[39] ? $this->formatarMoeda($data[39]) : 0;
                return [
                    'name' => $this->normalizeString($data[0] ?? null),
                    'info' => $this->normalizeString(($data[1] ?? null) . '<br>' . ($data[4] ?? null)),
                    'city' => $this->normalizeString($data[2] ?? null),
                    'type' => 'emissoras',
                    'state' => $state,
                    'street' => null,
                    'number' => null,
                    'total_liquido' => $total,
                    'cep' => 0,
                ];
            }
            return [];
        }

        if ($type === 'placas') {
            // Variáveis para armazenar informações do grupo atual
            static $currentVehicle = null;
            static $currentFormat = null;
            static $currentCity = null;
            // Verificar se o veículo mudou
            if (!empty($data[1])) { // Coluna "Veículo"
                $currentVehicle = $this->normalizeString($data[1]);
                $info = null; // Resetar o formato ao mudar de veículo
                $currentCity = null;   // Resetar a cidade ao mudar de veículo
            }
        
            // Verificar se a cidade mudou
            if (!empty($data[4])) { // Coluna "Cidade"
                $currentCity = $this->normalizeString($data[4]);
            }          
            // Verificar se a UF mudou
            if (!empty($data[5])) { // Coluna "UF"
                $currentCity = $this->normalizeString($data[5]);
            }
        
            // Verificar se o formato mudou
            if (!empty($data[6])) { // Coluna "Produto"
                $info = $this->normalizeString($data[6]) ;
            }
        
            // Validar e montar o array final
            if (!!$currentVehicle && !!$info && !!$data[12]) {
                $total = $data[12] ? $this->formatarMoeda($data[12]) : 0;
                return [
                    'name' => $currentVehicle,
                    'info' => $info,
                    'city' => $currentCity,
                    'type' => 'placas',
                    'state' => $state,
                    'street' => $this->normalizeString($data[6] ?? null), // Produto/Rua
                    'number' => 0,
                    'total_liquido' => $total,
                    'cep' => 0,
                ];
            }
            return [];
        }

        if ($type === 'empresa') {
            if (!!$data[0] && !!$data[1] && !!$data[2] && !!$data[3] && !!$data[4] && !!$data[5] && !!$data[6]) {
                return [
                    'name' => $this->normalizeString($data[2] ?? null), // Nome da empresa
                    'info' => $this->normalizeString($data[3] ?? '') . '  ' .$this->normalizeString($data[4] ?? ''). '  ' .$this->normalizeString($data[5] ?? '') , // Descrição
                    'city' => $this->normalizeString($data[0] ?? null), // Cidade
                    'state' => $this->normalizeString($data[10] ?? null), // Estado
                    'street' => $this->normalizeString($data[5] ?? null), // Rua
                    'number' => $this->normalizeString($data[6] ?? null), // Número
                    'cep' => $this->normalizeString($data[4] ?? null), // CEP
                ];
            }
            return [];
        }
        return [];
    }

    private function normalizeString($string): string
    {
        if (empty($string)) {
            return '';
        }

        // Converte para maiúsculas
        $string = mb_strtoupper($string, 'UTF-8');

        // Remove acentuação usando iconv
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        // Remove caracteres não alfanuméricos (exceto espaço)
        $string = preg_replace('/[^A-Z0-9\s]/', '', $string);

        // Remove espaços extras
        $string = trim(preg_replace('/\s+/', ' ', $string));

        return $string;
    }

    function formatarMoeda(string $valor): string
    {
        // Regex para manter apenas números (remove tudo o que não for dígito)
        $valorLimpo = preg_replace('/[^0-9]/', '', $valor);
    
        // Converte para float e formata com duas casas decimais
        $valorNumerico = (float) ($valorLimpo / 100); // Divide por 100 para adicionar as casas decimais
        $valorFormatado = number_format($valorNumerico, 2, '.', '');
    
        return $valorFormatado;
    }
// null,
// "PORTAL JR. CAPANEMA",
// "6959",
// "TROMBETA",
// "Lateral: 300 x 250 pixels",
// "mensal ",
// "R$ 1,200.00",
// "0%",
// "R$ 1,200.00",
// "1","mês",
// "R$ 960.00",
// "R$ 1,200.00","R$ 240.00","R$ 36.00","R$ 1,164.00",null] [2025-07-23 04:38:11

    public function show(Import $import)
    {
        return view('imports.show', compact('import'));
    }

    public function destroy(Import $import)
    {
        $import->delete();
        
        return redirect()->route('imports.index')
            ->with('success', 'Registro de importação removido com sucesso.');
    }
}