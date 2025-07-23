<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampanhaInertiaController extends Controller
{
        public function index(Request $request)
        {
            // Passa o import_id (ou null, se não for fornecido) para a view
            return Inertia::render('Map', [
                'importId' => $request->input('import_id'),
            ]);
        }

        public function show(Request $request)
        {
        // Passa o import_id (ou null, se não for fornecido) para a view
        return Inertia::render('Campanhas', [
            'importId' => $request->input('import_id'),
        ]);
    }}