<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Entity;
use App\Models\Address;
use App\Services\AddressService; // Importa o AddressService

class EntityImportService
{
    protected $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService; // Injeta o AddressService
    }

    /**
     * Processa o arquivo de importação e salva os dados no banco de dados.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function import($file)
    {
        try {
            // Armazena o arquivo temporariamente
            $filePath = $file->store('imports');

            // Lê o arquivo usando PhpSpreadsheet
            $spreadsheet = IOFactory::load(Storage::path($filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Remove o cabeçalho (primeira linha)
            array_shift($rows);

            // Array para armazenar erros e registros criados
            $createdEntities = [];
            $errors = [];

            // Itera sobre as linhas do arquivo
            foreach ($rows as $index => $row) {
                // Valida os dados da linha
                $validator = Validator::make([
                    'estado' => $row[0] ?? null,
                    'nome' => $row[1] ?? null,
                    'razao_social' => $row[2] ?? null,
                    'tipo' => $row[3] ?? null,
                    'endereco' => $row[4] ?? null,
                    'cep' => $row[5] ?? null,
                    'municipio' => $row[6] ?? null,
                    'pais' => $row[7] ?? null,
                    'cnpj_cpf' => $row[8] ?? null,
                    'praca' => $row[9] ?? null,
                    'rede' => $row[10] ?? null,
                    'codigo_tabela' => $row[11] ?? null,
                    'codigo' => $row[12] ?? null,
                    'email' => $row[13] ?? null,
                    'telefone' => $row[14] ?? null,
                    'id' => $row[15] ?? null,
                    'whatsapp' => $row[16] ?? null,
                ], [
                    'estado' => 'nullable|string|max:2',
                    'nome' => 'required|string|max:255',
                    'razao_social' => 'nullable|string|max:255',
                    'tipo' => 'nullable|string|max:2',
                    'endereco' => 'nullable|string|max:255',
                    'cep' => 'nullable|string|max:9',
                    'municipio' => 'nullable|string|max:255',
                    'pais' => 'nullable|string|max:255',
                    'cnpj_cpf' => 'nullable|string|max:20',
                    'praca' => 'nullable|integer',
                    'rede' => 'nullable|string|max:255',
                    'codigo_tabela' => 'nullable|integer',
                    'codigo' => 'nullable|integer',
                    'email' => 'nullable|email|max:255',
                    'telefone' => 'nullable|string|max:20',
                    'id' => 'nullable|integer',
                    'whatsapp' => 'nullable|string|max:20',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Erro na linha " . ($index + 2) . ": " . implode(", ", $validator->errors()->all());
                    continue;
                }

                // Extrai os dados do endereço
                $addressData = [
                    'city' => $row[6], // Município
                    'state' => $row[0], // Estado
                    'street' => $row[4], // Endereço
                    'number' => null, // Número pode ser extraído do endereço se necessário
                    'cep' => $row[5], // CEP
                ];

                // Busca ou cria o endereço usando o AddressService
                $address = $this->addressService->findOrCreateAddress($addressData);

                if (!$address) {
                    $errors[] = "Erro na linha " . ($index + 2) . ": Não foi possível criar ou encontrar o endereço.";
                    continue;
                }

                // Cria ou atualiza a entidade
                $entity = Entity::updateOrCreate(
                    ['cnpj_cpf' => $row[8]], // Chave única (CNPJ/CPF)
                    [
                        'name' => $row[1],
                        'razao_social' => $row[2],
                        'type' => $row[3],
                        'address_id' => $address->id,
                        'cnpj_cpf' => $row[8],
                        'codigo_tabela' => $row[11],
                        'codigo' => $row[12],
                        'praca' => $row[9],
                        'rede' => $row[10],
                    ]
                );

                $createdEntities[] = $entity;
            }

            // Remove o arquivo temporário
            Storage::delete($filePath);

            return [
                'success' => true,
                'message' => 'Importação concluída com sucesso.',
                'created_entities' => $createdEntities,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar o arquivo de importação.',
                'error' => $e->getMessage(),
            ];
        }
    }
}