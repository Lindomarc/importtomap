<?php

namespace App\Http\Controllers\Api;

use App\Models\Entity; // Substitua "Entity" pelo nome do seu modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\EntityImportService;

class EntityController extends Controller
{
    protected $importService;

    public function __construct(EntityImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Importa entidades a partir de um arquivo.
     */
    public function import(Request $request)
    {
        // Validação do upload do arquivo
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        // Chama o serviço de importação
        $result = $this->importService->import($request->file('file'));

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'created_entities' => count($result['created_entities']),
                'errors' => $result['errors'],
            ]);
        }

        return response()->json([
            'message' => $result['message'],
            'error' => $result['error'],
        ], 500);
    }
    /**
     * Lista todas as entidades com suporte a filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Constrói a query base
            $query = Entity::query();

            // Aplica filtros se fornecidos
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('cnpj_cpf', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Ordenação padrão
            $query->orderBy('created_at', 'desc');

            // Paginação
            $perPage = $request->input('per_page', 10);
            $entities = $query->paginate($perPage);

            return response()->json($entities);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar entidades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe os detalhes de uma entidade específica.
     */
    public function show($id): JsonResponse
    {
        try {
            $entity = Entity::findOrFail($id);

            return response()->json([
                'data' => $entity
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Entidade não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar entidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova entidade.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validação dos dados
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'cnpj_cpf' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:2',
                'cep' => 'nullable|string|max:9',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cria a entidade
            $entity = Entity::create($request->all());

            return response()->json([
                'message' => 'Entidade criada com sucesso',
                'data' => $entity
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar entidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma entidade existente.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Busca a entidade
            $entity = Entity::findOrFail($id);

            // Validação dos dados
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'cnpj_cpf' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:2',
                'cep' => 'nullable|string|max:9',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Atualiza a entidade
            $entity->update($request->only([
                'name', 'email', 'cnpj_cpf', 'address', 'city', 'state', 'cep'
            ]));

            return response()->json([
                'message' => 'Entidade atualizada com sucesso',
                'data' => $entity
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Entidade não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar entidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exclui uma entidade.
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Busca a entidade
            $entity = Entity::findOrFail($id);

            // Exclui a entidade
            $entity->delete();

            return response()->json([
                'message' => 'Entidade excluída com sucesso'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Entidade não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir entidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
}