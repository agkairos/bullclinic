<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_atendimento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0=dom, 1=seg ... 6=sab
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->unsignedInteger('duracao_consulta_minutos')->default(30);
            $table->unsignedInteger('intervalo_entre_consultas_minutos')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'clinica_id', 'dia_semana']);
        });

        Schema::create('intervalos_dia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio_intervalo');
            $table->time('hora_fim_intervalo');
            $table->timestamps();

            $table->index(['user_id', 'clinica_id', 'dia_semana']);
        });

        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tipo_servico_id')->nullable()->constrained('tipos_servico')->nullOnDelete();
            $table->enum('status', ['agendado', 'confirmado', 'aguardando', 'em_atendimento', 'finalizado', 'cancelado'])->default('agendado');
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->unsignedInteger('duracao_estimada_minutos')->default(30);
            $table->boolean('primeira_consulta')->default(false);
            $table->boolean('urgencia')->default(false);
            $table->text('observacoes_publicas')->nullable();
            $table->text('observacoes_internas')->nullable();
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamp('checkin_em')->nullable();
            $table->timestamp('atendimento_iniciado_em')->nullable();
            $table->timestamp('atendimento_finalizado_em')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->foreignId('cancelado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'data']);
            $table->index(['clinica_id', 'veterinario_id', 'data']);
            $table->index(['clinica_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
        Schema::dropIfExists('intervalos_dia');
        Schema::dropIfExists('horarios_atendimento');
    }
};
