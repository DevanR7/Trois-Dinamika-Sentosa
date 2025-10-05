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
        Schema::create('purchase_order_item_discounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items', 'item_id')->onDelete('cascade');
        $table->decimal('percentage', 5, 2); // Diskon dalam persen, misal: 10.50
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_item_discounts');
    }
};
