<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->morphs('documentavel'); // paciente, consulta, etc.
            $table->foreignId('uploaded_by_id')->constrained('users')->restrictOnDelete();
            $table->string('nome');
            $table->string('arquivo_url');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->default(0);
            $table->string('categoria')->nullable(); // exame, receita, atestado, outro
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinica_id', 'documentavel_type', 'documentavel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
