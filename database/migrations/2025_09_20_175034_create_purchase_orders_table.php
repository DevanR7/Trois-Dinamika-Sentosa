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
        Schema::create('purchase_orders', function (Blueprint $table) {
        $table->id('po_id');
        $table->string('po_number', 50)->unique();
        $table->foreignId('supplier_id')->constrained(table: 'suppliers', column: 'supplier_id');
        $table->foreignId('user_id_admin')->constrained(table: 'users', column: 'user_id');
        $table->date('order_date');

        // ✅ DIHAPUS ->after('order_date')
        $table->date('due_date')->nullable(); 

        $table->date('expected_delivery_date')->nullable();
        $table->decimal('total_amount', 15, 2);

        // ✅ DIHAPUS ->after('total_amount')
        $table->decimal('amount_paid', 15, 2)->default(0); 

        $table->enum('status', ['draft', 'ordered', 'completed', 'cancelled'])->default('draft');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
