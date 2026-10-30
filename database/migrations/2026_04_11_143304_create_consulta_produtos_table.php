<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('consulta_produtos', function (Blueprint $table) {
      $table->id();
      $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
      $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
      $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
      $table->decimal('quantidade', 10, 3)->comment('Quantidade utilizada/vendida');
      $table->decimal('valor_unitario', 10, 2)->comment('Preço unitário no momento da consulta');
      $table->decimal('subtotal', 10, 2)->storedAs('quantidade * valor_unitario')->comment('Total calculado automaticamente');
      $table->boolean('aplicado_na_clinica')->default(true)->comment('true=usado na consulta, false=vendido para levar');
      $table->text('observacoes')->nullable();
      $table->foreignId('usuario_id')->constrained('users')->comment('Quem registrou');
      $table->timestamps();
      $table->softDeletes();

      $table->index(['clinica_id', 'consulta_id']);
      $table->index(['clinica_id', 'produto_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('consulta_produtos');
  }
};
