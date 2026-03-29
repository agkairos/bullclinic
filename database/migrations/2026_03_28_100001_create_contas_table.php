<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->enum('status_pagamento', ['trial', 'pendente', 'pago', 'atrasado', 'cancelado'])->default('trial');
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('woovi_customer_id')->nullable()->index();
            $table->timestamp('data_vencimento')->nullable();
            $table->timestamp('data_trial_fim')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
