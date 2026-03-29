<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->boolean('ativa')->default(true);
            $table->enum('status_pagamento', ['trial', 'pendente', 'pago', 'atrasado', 'cancelado'])->default('trial');
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('woovi_customer_id')->nullable()->index();
            $table->timestamp('data_vencimento')->nullable();
            $table->timestamp('data_trial_fim')->nullable();
            $table->decimal('limite_storage_usado_gb', 10, 4)->default(0);
            $table->json('configs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinicas');
    }
};
