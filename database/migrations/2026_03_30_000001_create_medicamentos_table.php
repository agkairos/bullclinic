<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->index();
            $table->string('nome');
            $table->string('forma_farmaceutica')->nullable();
            $table->string('dosagem_padrao')->nullable();
            $table->string('via_administracao')->nullable();
            $table->string('frequencia_padrao')->nullable();
            $table->string('duracao_padrao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
