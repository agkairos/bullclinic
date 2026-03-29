<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona conta_id nas clínicas e torna campos de assinatura nullable
        // (dados de assinatura sobem para a tabela contas)
        Schema::table('clinicas', function (Blueprint $table) {
            $table->foreignId('conta_id')->nullable()->after('id')->constrained('contas')->nullOnDelete();

            // Campos de assinatura tornam-se nullable (migrados para contas)
            $table->string('stripe_customer_id')->nullable()->change();
            $table->string('stripe_subscription_id')->nullable()->change();
            $table->string('woovi_customer_id')->nullable()->change();
        });

        // Adiciona conta_ativa_id nos usuários
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('conta_ativa_id')->nullable()->after('clinica_ativa_id')->constrained('contas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conta_ativa_id');
        });

        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conta_id');
        });
    }
};
