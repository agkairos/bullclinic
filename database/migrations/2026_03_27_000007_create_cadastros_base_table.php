<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['clinica_id', 'nome']);
        });

        Schema::create('tipos_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('duracao_minutos')->default(30);
            $table->decimal('preco', 10, 2)->default(0);
            $table->string('cor', 7)->default('#00c1b3'); // hex para agenda
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('laboratorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('tempo_medio_resultado_dias')->default(0);
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_exame', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('categoria')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('laboratorio_tipos_exame', function (Blueprint $table) {
            $table->foreignId('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignId('tipo_exame_id')->constrained('tipos_exame')->cascadeOnDelete();
            $table->primary(['laboratorio_id', 'tipo_exame_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratorio_tipos_exame');
        Schema::dropIfExists('tipos_exame');
        Schema::dropIfExists('laboratorios');
        Schema::dropIfExists('tipos_servico');
        Schema::dropIfExists('especialidades');
    }
};
