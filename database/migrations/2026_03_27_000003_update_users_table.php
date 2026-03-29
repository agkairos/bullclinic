<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinica_ativa_id')->nullable()->after('id')->constrained('clinicas')->nullOnDelete();
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->string('crmv', 20)->nullable()->after('avatar');
            $table->string('cargo')->nullable()->after('crmv');
            $table->boolean('ativo')->default(true)->after('cargo');
            $table->timestamp('ultimo_acesso_em')->nullable()->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinica_ativa_id');
            $table->dropColumn(['avatar', 'crmv', 'cargo', 'ativo', 'ultimo_acesso_em']);
        });
    }
};
