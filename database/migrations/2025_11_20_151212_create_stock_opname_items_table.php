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
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id('opname_item_id');
            $table->foreignId('opname_id')->constrained('stock_opnames', 'opname_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            
            // UBAH SEMUA INTEGER MENJADI DECIMAL(15, 2)
            $table->decimal('system_qty', 15, 2);   // Stok di komputer (bisa pecahan)
            $table->decimal('physical_qty', 15, 2); // Stok fisik (bisa pecahan)
            $table->decimal('difference', 15, 2);   // Selisih (bisa negatif dan pecahan)
            
            // OPTIONAL: Sesuaikan presisi HPP dengan tabel products (19, 4) agar lebih akurat
            $table->decimal('cost_per_unit', 19, 4); 
            $table->decimal('adjustment_value', 15, 2); 
            
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
