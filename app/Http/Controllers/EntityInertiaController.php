<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EntityInertiaController extends Controller
{
    /**
     * Exibe a página principal com a lista de entidades.
     */
    public function index(Request $request)
    {
        // Passa o import_id (ou null, se não for fornecido) para a view
        return Inertia::render('Entities', [
            'importId' => $request->input('import_id'),
        ]);
    }
}