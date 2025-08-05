<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            // Chave primária
            $table->id();

            // Dados principais do fornecedor
            $table->string('name')->nullable(); // Nome do fornecedor
            $table->string('razao_social')->nullable(); // Razão Social
            $table->string('type', 2)->nullable(); // Tipo (ex.: OH, IN, RD, etc.)
            $table->string('cnpj_cpf', 20)->nullable(); // CNPJ ou CPF
            $table->unsignedBigInteger('codigo_tabela')->nullable(); // Código Tabela
            $table->unsignedBigInteger('codigo')->nullable(); // Código
            $table->unsignedBigInteger('praca')->nullable(); // Praça
            $table->string('rede')->nullable(); // Rede

            // Relacionamento com a tabela de endereços
            $table->unsignedBigInteger('address_id')->nullable();
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};