<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->string('rg', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'nome']);
            $table->index(['clinica_id', 'cpf']);
        });

        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nome');
            $table->string('especie'); // cao, gato, ave, roedor, reptil, outro
            $table->string('raca')->nullable();
            $table->enum('sexo', ['macho', 'femea', 'nao_informado'])->default('nao_informado');
            $table->date('data_nascimento')->nullable();
            $table->string('cor')->nullable();
            $table->decimal('peso', 6, 3)->nullable(); // kg
            $table->decimal('altura', 5, 2)->nullable(); // cm
            $table->string('microchip')->nullable();
            $table->string('tatuagem')->nullable();
            $table->text('alergias')->nullable();
            $table->text('condicoes_especiais')->nullable();
            $table->text('restricoes_alimentares')->nullable();
            $table->text('medicamentos_cronicos')->nullable();
            $table->text('historico_resumido')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'cliente_id']);
            $table->index(['clinica_id', 'nome']);
        });

        Schema::create('paciente_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('url');
            $table->unsignedBigInteger('tamanho_bytes')->default(0);
            $table->boolean('principal')->default(false);
            $table->timestamps();
        });

        Schema::create('paciente_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('tag');
            $table->timestamps();

            $table->unique(['paciente_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_tags');
        Schema::dropIfExists('paciente_fotos');
        Schema::dropIfExists('pacientes');
        Schema::dropIfExists('clientes');
    }
};
