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
         Schema::create('sales_order_items', function (Blueprint $table) {
        $table->id('item_id'); // Primary key

        // Relasi ke tabel sales_orders dan products
        $table->foreignId('order_id')->constrained('sales_orders', 'order_id')->onDelete('cascade');
        $table->foreignId('product_id')->constrained('products', 'product_id');

        // Rincian item
        $table->integer('quantity');
        $table->decimal('price_per_unit', 15, 2); // Harga saat pesanan dibuat
        $table->decimal('subtotal', 15, 2);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
