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
            
            $table->integer('system_qty'); // Stok di komputer saat opname
            $table->integer('physical_qty'); // Stok fisik hasil hitungan
            $table->integer('difference'); // physical - system
            
            $table->decimal('cost_per_unit', 15, 2); // HPP saat opname
            $table->decimal('adjustment_value', 15, 2); // difference * cost_per_unit
            
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
