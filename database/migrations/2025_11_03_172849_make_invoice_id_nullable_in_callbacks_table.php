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
        Schema::table('payment_gateway_callbacks', function (Blueprint $table) {
            // 1. Hapus foreign key yang lama (yang default-nya onDelete('cascade'))
            $table->dropForeign(['invoice_id']);
            
            // 2. Ubah kolom menjadi nullable (ini membutuhkan doctrine/dbal)
            // Kita gunakan tipe data dasarnya, bukan helper 'foreignId'
            $table->unsignedBigInteger('invoice_id')->nullable()->change();
            
            // 3. Tambahkan kembali foreign key, tapi sekarang dengan onDelete('set null')
            // Ini penting karena kolomnya sekarang boleh null
            $table->foreign('invoice_id')
                  ->references('invoice_id')
                  ->on('sales_invoices')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateway_callbacks', function (Blueprint $table) {
            // 1. Hapus foreign key 'set null'
            $table->dropForeign(['invoice_id']);
            
            // 2. Ubah kolom kembali menjadi NOT nullable
            $table->unsignedBigInteger('invoice_id')->nullable(false)->change();
            
            // 3. Kembalikan foreign key 'cascade' yang asli
            $table->foreign('invoice_id')
                  ->references('invoice_id')
                  ->on('sales_invoices')
                  ->onDelete('cascade');
        });
    }
};