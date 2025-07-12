<?php
use App\Http\Controllers\Api\CampanhaController;


// Rota para listar todas as campanhas
Route::get('/campanhas', [CampanhaController::class, 'index']);

// Rota alternativa com filtros (opcional)
Route::get('/campanhas/search', [CampanhaController::class, 'indexWithFilters']);

// Rota para criar uma nova campanha
Route::post('/campanhas', [CampanhaController::class, 'store']);

// Rota para atualizar uma campanha existente
Route::put('/campanhas/{id}', [CampanhaController::class, 'update']);

// Rota para excluir uma campanha
Route::delete('/campanhas/{id}', [CampanhaController::class, 'destroy']);