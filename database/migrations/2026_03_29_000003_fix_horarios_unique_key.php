<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios_atendimento', function (Blueprint $table) {
            // Garante índice em user_id antes de dropar o unique composto
            // (MySQL exige índice para suportar a FK de user_id)
            $table->index('user_id', 'horarios_user_id_idx');
            $table->dropUnique('horarios_atendimento_user_id_clinica_id_dia_semana_unique');
            $table->unique(['user_id', 'clinica_id', 'dia_semana', 'ordem'], 'horarios_user_clinica_dia_ordem_unique');
        });
    }

    public function down(): void
    {
        Schema::table('horarios_atendimento', function (Blueprint $table) {
            $table->dropUnique('horarios_user_clinica_dia_ordem_unique');
            $table->dropIndex('horarios_user_id_idx');
            $table->unique(['user_id', 'clinica_id', 'dia_semana']);
        });
    }
};
