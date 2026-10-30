<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('documentos', function (Blueprint $table) {
      $table->string('documentavel_type')->nullable()->change();
      $table->unsignedBigInteger('documentavel_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('documentos', function (Blueprint $table) {
      $table->string('documentavel_type')->nullable(false)->change();
      $table->unsignedBigInteger('documentavel_id')->nullable(false)->change();
    });
  }
};
