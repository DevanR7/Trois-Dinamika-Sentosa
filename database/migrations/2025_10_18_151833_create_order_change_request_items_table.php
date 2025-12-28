<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_change_request_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('order_change_request_id')
                ->constrained('order_change_requests', 'request_id')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products', 'product_id');
            $table->decimal('original_quantity', 15, 2)->nullable(); 
            $table->decimal('requested_quantity', 15, 2);
            $table->enum('action', ['add', 'remove', 'update_qty']);
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_change_request_items');
    }
};