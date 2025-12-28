<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id('opname_item_id');
            $table->foreignId('opname_id')
                ->constrained('stock_opnames', 'opname_id')
                ->onDelete('cascade');
            $table->foreignId('product_id')
                ->constrained('products', 'product_id');
            $table->decimal('system_qty', 15, 2);
            $table->decimal('physical_qty', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->decimal('cost_per_unit', 19, 4);
            $table->decimal('adjustment_value', 15, 2);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};