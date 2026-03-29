<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->cascadeOnDelete();
            $table->string('nome'); // super_admin, admin, veterinario, recepcionista, personalizado
            $table->string('display_name');
            $table->text('descricao')->nullable();
            $table->boolean('is_system')->default(false); // roles padrão não editáveis
            $table->timestamps();

            $table->unique(['clinica_id', 'nome']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique(); // ex: ver_agenda, criar_agendamentos
            $table->string('modulo'); // agenda, prontuario, estoque, financeiro, admin, chat
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
