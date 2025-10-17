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
        Schema::create('client_orders', function (Blueprint $table) {
        $table->id('client_order_id');
        $table->foreignId('client_id')->constrained('clients', 'client_id');
        $table->date('order_date');
        $table->decimal('total_amount', 15, 2);
        $table->string('status')->default('pending_review'); // Status: pending_review, approved, rejected
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_orders');
    }
};
