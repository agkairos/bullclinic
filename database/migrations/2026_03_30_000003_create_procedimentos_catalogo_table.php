<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedimentos_catalogo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinica_id');
            $table->foreign('clinica_id', 'fk_proc_catalogo_clinica')->references('id')->on('clinicas');
            $table->index('clinica_id');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('valor_padrao', 10, 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedimentos_catalogo');
    }
};
