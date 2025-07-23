<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportIdToCampanhasTable extends Migration
{
    public function up()
    {
        Schema::table('campanhas', function (Blueprint $table) {
            // Adiciona a coluna import_id como chave estrangeira
            $table->unsignedBigInteger('import_id')->nullable(); // Permite NULL se necessário
            $table->foreign('import_id')->references('id')->on('imports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('campanhas', function (Blueprint $table) {
            // Remove a chave estrangeira e a coluna
            $table->dropForeign(['import_id']);
            $table->dropColumn('import_id');
        });
    }
}