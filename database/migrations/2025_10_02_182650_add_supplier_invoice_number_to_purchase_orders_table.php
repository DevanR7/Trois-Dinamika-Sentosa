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
    Schema::table('purchase_orders', function (Blueprint $table) {
        $table->string('supplier_invoice_number')->nullable()->after('po_number');
    });
}

public function down(): void
{
    Schema::table('purchase_orders', function (Blueprint $table) {
        $table->dropColumn('supplier_invoice_number');
    });
}
};
