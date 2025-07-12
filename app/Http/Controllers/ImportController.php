<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    // Método para exibir o formulário de upload
    public function index()
    {
        return view('imports.upload');
    }

    // Método para processar a importação da planilha
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'type' => 'required|string', // Define o tipo de importação (ex.: 'campanhas')
        ]);

        $type = $request->input('type');

        try {
            switch ($type) {
                case 'campanhas':
                    Excel::import(new \App\Imports\CampanhasImport, $request->file('file'));
                    break;
                // Adicione outros casos aqui para diferentes tipos de importação
                default:
                    return redirect()->back()->withErrors(['error' => 'Tipo de importação inválido.']);
            }

            return redirect()->back()->with('success', 'Planilha importada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Erro ao importar planilha: ' . $e->getMessage()]);
        }
    }
}