<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Mengubah kolom agar bisa bernilai NULL dan defaultnya menjadi NULL
            $table->integer('stock_quantity')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Mengembalikan seperti semula jika di-rollback
            $table->integer('stock_quantity')->nullable(false)->default(0)->change();
        });
    }
};