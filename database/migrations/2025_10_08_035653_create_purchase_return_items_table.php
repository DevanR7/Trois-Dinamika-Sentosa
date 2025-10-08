<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('return_id')->constrained('purchase_returns', 'return_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};