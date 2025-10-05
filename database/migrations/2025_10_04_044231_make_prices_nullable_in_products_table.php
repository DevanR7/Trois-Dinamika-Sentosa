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
        // Mengubah kolom agar bisa bernilai NULL
        $table->decimal('purchase_price', 15, 2)->nullable()->default(null)->change();
        $table->decimal('selling_price', 15, 2)->nullable()->default(null)->change();
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Mengembalikan seperti semula jika di-rollback
        $table->decimal('purchase_price', 15, 2)->nullable(false)->default(0.00)->change();
        $table->decimal('selling_price', 15, 2)->nullable(false)->change();
    });
}
};
