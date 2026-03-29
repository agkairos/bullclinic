<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'clinica_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('concedida')->default(true); // false = revogada explicitamente
            $table->timestamps();

            $table->unique(['user_id', 'clinica_id', 'permission_id']);
        });

        Schema::create('sessoes_unicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete();
            $table->string('session_token')->unique();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('ultimo_ativo_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_unicas');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_clinicas');
    }
};
