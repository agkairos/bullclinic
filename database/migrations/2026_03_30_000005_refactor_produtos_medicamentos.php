<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona tipo nas categorias
        Schema::table('categorias_produto', function (Blueprint $table) {
            $table->enum('tipo', ['material', 'medicamento', 'ambos'])->default('ambos')->after('nome');
        });

        // Adiciona tipo e campos farmacêuticos nos produtos
        Schema::table('produtos', function (Blueprint $table) {
            $table->enum('tipo', ['material', 'medicamento'])->default('material')->after('nome');
            $table->string('forma_farmaceutica')->nullable()->after('tipo');
            $table->string('dosagem_padrao')->nullable()->after('forma_farmaceutica');
            $table->string('via_administracao_padrao')->nullable()->after('dosagem_padrao');
            $table->string('frequencia_padrao')->nullable()->after('via_administracao_padrao');
            $table->string('duracao_padrao')->nullable()->after('frequencia_padrao');
            $table->boolean('controlado')->default(false)->after('duracao_padrao');
        });

        // Vincula item de receita ao produto do estoque (nullable para manter compatibilidade)
        Schema::table('receita_itens', function (Blueprint $table) {
            $table->foreignId('produto_id')->nullable()->after('receita_id')
                ->constrained('produtos')->nullOnDelete();
        });

        // Remove tabela medicamentos que foi criada errada
        Schema::dropIfExists('medicamentos');
    }

    public function down(): void
    {
        Schema::table('receita_itens', function (Blueprint $table) {
            $table->dropForeign(['produto_id']);
            $table->dropColumn('produto_id');
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'forma_farmaceutica', 'dosagem_padrao', 'via_administracao_padrao', 'frequencia_padrao', 'duracao_padrao', 'controlado']);
        });

        Schema::table('categorias_produto', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
