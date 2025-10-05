<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->softDeletes(); // Perintah ini akan menambahkan kolom 'deleted_at'
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropSoftDeletes(); // Perintah untuk rollback
    });
}
};
