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
        Schema::create('client_order_items', function (Blueprint $table) {
        $table->id('item_id');
        $table->foreignId('client_order_id')->constrained('client_orders', 'client_order_id')->onDelete('cascade');
        $table->foreignId('product_id')->constrained('products', 'product_id');
        $table->integer('quantity');
        $table->decimal('price_per_unit', 15, 2);
        $table->decimal('subtotal', 15, 2);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_order_items');
    }
};
