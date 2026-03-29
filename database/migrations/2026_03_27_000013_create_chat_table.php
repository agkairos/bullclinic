<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('criado_por_id')->constrained('users')->restrictOnDelete();
            $table->string('nome')->nullable(); // null para conversas individuais
            $table->enum('tipo', ['individual', 'grupo', 'canal'])->default('individual');
            $table->boolean('arquivada')->default(false);
            $table->timestamps();

            $table->index(['clinica_id', 'tipo']);
        });

        Schema::create('conversa_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained('conversas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('silenciada')->default(false);
            $table->timestamp('entrou_em')->nullable();
            $table->timestamp('saiu_em')->nullable();
            $table->timestamps();

            $table->unique(['conversa_id', 'user_id']);
        });

        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained('conversas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('resposta_a_id')->nullable()->constrained('mensagens')->nullOnDelete();
            $table->enum('tipo', ['texto', 'audio', 'imagem'])->default('texto');
            $table->text('conteudo')->nullable();
            $table->string('arquivo_url')->nullable();
            $table->unsignedBigInteger('arquivo_tamanho_bytes')->nullable();
            $table->unsignedInteger('duracao_audio_segundos')->nullable();
            $table->boolean('fixada')->default(false);
            $table->timestamp('deletada_em')->nullable();
            $table->timestamps();

            $table->index(['conversa_id', 'created_at']);
        });

        Schema::create('mensagem_leituras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensagem_id')->constrained('mensagens')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('lida_em');
            $table->timestamps();

            $table->unique(['mensagem_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagem_leituras');
        Schema::dropIfExists('mensagens');
        Schema::dropIfExists('conversa_membros');
        Schema::dropIfExists('conversas');
    }
};
