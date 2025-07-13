<?php

namespace App\Http\Controllers\Api;

use App\Models\Campanha;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
class CampanhaController extends Controller
{

    public function list(){
        $items = Campanha::all();
        $data= [];
        foreach ($items as $item) {
            if ($item->lat && $item->lng){
                $data[] = [
                    'name' => $item->veiculo,
                    'info' => $item->campanha,
                    'lat' => $item->lat,
                    'lng' => $item->lng,
                    'color' =>  "blue",
                ];
            }
        }
        
        return response()->json([
            'data' => $data
        ], 200);
    }
    /**
     * Método para listar todas as campanhas com paginação
     */
    public function index(Request $request)
    {
        // Validar parâmetros de paginação
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        // Obter parâmetros de paginação (com valores padrão)
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Buscar campanhas com paginação
        $campanhas = Campanha::orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Retornar dados formatados para o frontend
        return response()->json([
            'data' => $campanhas->items(),
            'current_page' => $campanhas->currentPage(),
            'last_page' => $campanhas->lastPage(),
            'per_page' => $campanhas->perPage(),
            'total' => $campanhas->total(),
            'from' => $campanhas->firstItem(),
            'to' => $campanhas->lastItem(),
            'has_more_pages' => $campanhas->hasMorePages(),
            'path' => $campanhas->path(),
            'next_page_url' => $campanhas->nextPageUrl(),
            'prev_page_url' => $campanhas->previousPageUrl()
        ]);
    }

    /**
     * Método alternativo com busca e filtros (opcional)
     */
    public function indexWithFilters(Request $request): JsonResponse
    {
        // Validar parâmetros
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'search' => 'string|max:255',
            'cliente' => 'string|max:255',
            'veiculo' => 'string|max:255',
            'meio' => 'string|max:255',
            'lat' => 'string|max:255',
            'lng' => 'string|max:255'
        ]);

        // Obter parâmetros
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $cliente = $request->input('cliente');
        $veiculo = $request->input('veiculo');
        $meio = $request->input('meio');
        $meio = $request->input('lat');
        $lng= $request->input('lng');

        // Construir query com filtros
        $query = Campanha::query();

        // Filtro de busca geral
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('campanha', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%")
                  ->orWhere('veiculo', 'LIKE', "%{$search}%")
                  ->orWhere('meio', 'LIKE', "%{$search}%")
                  ->orWhere('praca', 'LIKE', "%{$search}%");
            });
        }

        // Filtros específicos
        if ($cliente) {
            $query->where('cliente', 'LIKE', "%{$cliente}%");
        }

        if ($veiculo) {
            $query->where('veiculo', 'LIKE', "%{$veiculo}%");
        }

        if ($meio) {
            $query->where('meio', 'LIKE', "%{$meio}%");
        }

        // Ordenar e paginar
        $campanhas = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Retornar dados formatados
        return response()->json([
            'data' => $campanhas->items(),
            'current_page' => $campanhas->currentPage(),
            'last_page' => $campanhas->lastPage(),
            'per_page' => $campanhas->perPage(),
            'total' => $campanhas->total(),
            'from' => $campanhas->firstItem(),
            'to' => $campanhas->lastItem(),
            'has_more_pages' => $campanhas->hasMorePages(),
            'path' => $campanhas->path(),
            'next_page_url' => $campanhas->nextPageUrl(),
            'prev_page_url' => $campanhas->previousPageUrl(),
            'filters' => [
                'search' => $search,
                'cliente' => $cliente,
                'veiculo' => $veiculo,
                'meio' => $meio
            ]
        ]);
    }
    
    // Método para importar campanhas a partir de uma planilha
    public function importar(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            // Itera sobre as linhas da planilha (ignorando o cabeçalho)
            $rows = $sheet->toArray();
            unset($rows[0]); // Remove a primeira linha (cabeçalho)

            foreach ($rows as $row) {
                Campanha::create([
                    'campanha' => $row[0] ?? null,
                    'cliente' => $row[1] ?? null,
                    'veiculo' => $row[2] ?? null,
                    'meio' => $row[3] ?? null,
                    'praca' => $row[4] ?? null,
                ]);
            }

            return response()->json(['message' => 'Planilha importada com sucesso!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao importar planilha: ' . $e->getMessage()], 500);
        }
    }

   /**
     * Atualizar uma campanha específica
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Validar dados de entrada
            $request->validate([
                'campanha' => 'nullable|string|max:255',
                'cliente' => 'nullable|string|max:255',
                'veiculo' => 'nullable|string|max:255',
                'meio' => 'nullable|string|max:255',
                'praca' => 'nullable|string|max:255',
                'lat' => 'nullable|string|max:255',
                'lng' => 'nullable|string|max:255'
            ]);

            // Buscar a campanha
            $campanha = Campanha::findOrFail($id);

            // Atualizar apenas os campos fornecidos
            $campanha->update($request->only([
                'campanha', 'cliente', 'veiculo', 'meio', 'praca', 'lat', 'lng'
            ]));

            return response()->json([
                'message' => 'Campanha atualizada com sucesso',
                'data' => $campanha
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campanha não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar campanha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar uma campanha
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Buscar a campanha
            $campanha = Campanha::findOrFail($id);

            // Deletar a campanha
            $campanha->delete();

            return response()->json([
                'message' => 'Campanha deletada com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campanha não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao deletar campanha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir uma campanha específica
     */
    public function show($id): JsonResponse
    {
        try {
            $campanha = Campanha::findOrFail($id);
            
            return response()->json([
                'data' => $campanha
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campanha não encontrada'
            ], 404);
        }
    }
}