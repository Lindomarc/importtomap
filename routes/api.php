<?php
use App\Http\Controllers\Api\CampanhaController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\EntityController;


// Rota para listar todas as campanhas
Route::get('/campanhas', [CampanhaController::class, 'index']);

// Rota para listar todas as campanhas
Route::get('/campanhas/list', [CampanhaController::class, 'list']);

// Rota alternativa com filtros (opcional)
Route::get('/campanhas/search', [CampanhaController::class, 'indexWithFilters']);

// Rota para criar uma nova campanha
Route::post('/campanhas', [CampanhaController::class, 'store']);

// Rota para atualizar uma campanha existente
Route::put('/campanhas/{id}', [CampanhaController::class, 'update']);

// Rota para excluir uma campanha
Route::delete('/campanhas/{id}', [CampanhaController::class, 'destroy']);

Route::apiResource('entities', EntityController::class);
Route::post('/entities/import', [EntityController::class, 'import'])->name('entities.import');
// Adicione essas rotas ao seu arquivo web.php

Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::get('/upload', [ImportController::class, 'upload'])->name('upload');
    Route::post('/store', [ImportController::class, 'store'])->name('import');
    Route::get('/{import}', [ImportController::class, 'show'])->name('show');
    Route::delete('/{import}', [ImportController::class, 'destroy'])->name('destroy');
});
