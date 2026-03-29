<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['clinica_id', 'nome']);
        });

        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('site')->nullable();
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
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_produto')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->string('nome');
            $table->string('codigo_barras')->nullable();
            $table->string('unidade_medida')->default('un'); // un, ml, mg, kg, cx
            $table->decimal('quantidade_estoque', 10, 3)->default(0);
            $table->decimal('estoque_minimo', 10, 3)->default(0);
            $table->decimal('preco_custo', 10, 2)->default(0);
            $table->decimal('preco_venda', 10, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'nome']);
            $table->index(['clinica_id', 'codigo_barras']);
        });

        Schema::create('tipos_movimentacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo_base', ['entrada', 'saida', 'ajuste']);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['clinica_id', 'nome']);
        });

        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('tipo_movimentacao_id')->constrained('tipos_movimentacao')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->nullOnDelete();
            $table->decimal('quantidade', 10, 3);
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamp('movimentado_em');
            $table->timestamps();

            $table->index(['clinica_id', 'produto_id']);
            $table->index(['clinica_id', 'movimentado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
        Schema::dropIfExists('tipos_movimentacao');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('categorias_produto');
    }
};
