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
    Schema::create('purchase_order_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('po_id')->constrained('purchase_orders', 'po_id')->onDelete('cascade');
        $table->foreignId('received_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
        $table->date('payment_date');
        $table->decimal('amount', 15, 2);
        $table->string('payment_method')->default('manual_transfer'); // cash, manual_transfer, dll
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('purchase_order_payments');
}
};
