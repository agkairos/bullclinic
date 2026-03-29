<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->decimal('preco_mensal', 10, 2)->default(0);
            $table->decimal('preco_anual', 10, 2)->default(0);
            $table->unsignedInteger('limite_usuarios')->default(1);
            $table->unsignedInteger('limite_clinicas')->default(1);
            $table->unsignedInteger('limite_consultas_mes')->default(0);
            $table->unsignedInteger('limite_storage_gb')->default(1);
            $table->boolean('consultas_ilimitadas')->default(false);
            $table->boolean('clinicas_ilimitadas')->default(false);
            $table->boolean('chat_audio')->default(false);
            $table->unsignedInteger('chat_audio_duracao_segundos')->default(0);
            $table->boolean('chat_grupos')->default(false);
            $table->unsignedInteger('chat_grupos_max_membros')->default(0);
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_mensal_id')->nullable();
            $table->string('stripe_price_anual_id')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
