<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo', ['receita', 'despesa']);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['clinica_id', 'nome', 'tipo']);
        });

        Schema::create('lancamentos_financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->enum('tipo', ['receita', 'despesa']);
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->enum('forma_pagamento', ['dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia', 'outro'])->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pago', 'pendente', 'atrasado', 'cancelado'])->default('pendente');
            $table->boolean('recorrente')->default(false);
            $table->enum('periodicidade', ['mensal', 'anual'])->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'tipo', 'status']);
            $table->index(['clinica_id', 'data_vencimento']);
            $table->index(['clinica_id', 'data_pagamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_financeiros');
        Schema::dropIfExists('categorias_financeiras');
    }
};
