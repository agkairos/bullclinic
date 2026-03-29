<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->enum('tipo_consulta', ['primeira_consulta', 'retorno', 'emergencia'])->default('primeira_consulta');
            $table->text('anamnese')->nullable();
            $table->text('exame_fisico')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('prognostico')->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('status', ['em_andamento', 'finalizada'])->default('em_andamento');
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'paciente_id']);
            $table->index(['clinica_id', 'veterinario_id']);
        });

        Schema::create('receitas_medicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->text('instrucoes_gerais')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('emitida_em')->nullable();
            $table->timestamps();
        });

        Schema::create('receita_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receita_id')->constrained('receitas_medicas')->cascadeOnDelete();
            $table->string('medicamento_nome');
            $table->string('dosagem')->nullable();
            $table->string('forma_farmaceutica')->nullable();
            $table->string('quantidade')->nullable();
            $table->string('frequencia')->nullable();
            $table->string('duracao_tratamento')->nullable();
            $table->string('via_administracao')->nullable();
            $table->text('instrucoes_especificas')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
        });

        Schema::create('exames_solicitados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tipo_exame_id')->nullable()->constrained('tipos_exame')->nullOnDelete();
            $table->foreignId('laboratorio_id')->nullable()->constrained('laboratorios')->nullOnDelete();
            $table->text('descricao')->nullable();
            $table->text('instrucoes')->nullable();
            $table->enum('status', ['solicitado', 'em_analise', 'concluido', 'cancelado'])->default('solicitado');
            $table->timestamp('solicitado_em')->nullable();
            $table->timestamp('previsao_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('resultados_exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exame_solicitado_id')->constrained('exames_solicitados')->cascadeOnDelete();
            $table->string('arquivo_url');
            $table->unsignedBigInteger('arquivo_tamanho_bytes')->default(0);
            $table->text('interpretacao')->nullable();
            $table->timestamp('recebido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('procedimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo_procedimento');
            $table->text('descricao')->nullable();
            $table->enum('status', ['planejado', 'realizado', 'cancelado'])->default('planejado');
            $table->timestamp('realizado_em')->nullable();
            $table->decimal('valor_cobrado', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('vacinacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->nullOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->string('vacina_nome');
            $table->string('fabricante')->nullable();
            $table->string('lote')->nullable();
            $table->date('data_aplicacao');
            $table->date('proxima_dose_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->text('reacoes_adversas')->nullable();
            $table->timestamps();

            $table->index(['clinica_id', 'paciente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacinacoes');
        Schema::dropIfExists('procedimentos');
        Schema::dropIfExists('resultados_exames');
        Schema::dropIfExists('exames_solicitados');
        Schema::dropIfExists('receita_itens');
        Schema::dropIfExists('receitas_medicas');
        Schema::dropIfExists('consultas');
    }
};
