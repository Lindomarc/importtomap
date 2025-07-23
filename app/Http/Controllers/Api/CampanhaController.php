<?php

namespace App\Http\Controllers\Api;

use App\Models\Campanha;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CampanhaController extends Controller
{

    public function list(Request $request){
        
        // Log::info("message",[$request->all()]);
        // Verifica se o parâmetro 'import_id' foi passado na requisição
        if ($request->has('import_id')) {
            // Se 'import_id' existe, filtra os registros pelo ID da importação
            $items = Campanha::where('import_id', $request->input('import_id'))->get();
        } else {
            // Se 'import_id' não existe, filtra os registros a partir do dia 1 do mês atual
            $startOfMonth = now()->startOfMonth(); // Obtém o primeiro dia do mês atual
            $items = Campanha::whereDate('created_at', '>=', $startOfMonth)->get();
        }
    
        // Formata os dados para o retorno
        $data = [];
        foreach ($items as $item) {
            if ($item->lat && $item->lng) {
                $data[] = [
                    'name' => $item->name,
                    'info' => $item->info,
                    'color' => $item->color,
                    'type' => $item->type,
                    'lat' => $item->lat,
                    'lng' => $item->lng,
                    'total_liquido' => $item->total_liquido,
                ];
            }
        }
    
        // Retorna os dados em formato JSON
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
        if ($request->has('import_id')) {
            $campanhas = Campanha::orderBy('id', 'desc')
            ->where('import_id', $request->input('import_id'))
            ->paginate($perPage, ['*'], 'page', $page);

        } else {
            $campanhas = Campanha::orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        }
        
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
            'name' => 'string|max:255',
            'info' => 'string|max:255',
            'type' => 'string|max:255',
            'lat' => 'string|max:255',
            'lng' => 'string|max:255'
        ]);

        // Obter parâmetros
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $name = $request->input('name');
        $type = $request->input('type');
        $info = $request->input('info');
        $lat = $request->input('lat');
        $lng= $request->input('lng');

        // Construir query com filtros
        $query = Campanha::query();

        if ($request->has('import_id')) {
            // Se 'import_id' existe, filtra os registros pelo ID da importação
            // $items = Campanha::orWhere('import_id', $request->input('import_id'))->get();
            $query->where(function ($q) use ($search) {
                $q->where('import_id', $request->input('import_id'))
                ->orWhere('info', 'LIKE', "%{$search}%")
                ->orWhere('type', 'LIKE', "%{$search}%");
            });
        } else {
                // Filtro de busca geral
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('info', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }
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
                'name' => $name,
                'info' => $info,
                'type' => $type
            ]
        ]);
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