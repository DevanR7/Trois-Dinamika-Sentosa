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
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->after('due_date')->default(0);
            $table->decimal('tax_percentage', 5, 2)->after('subtotal')->default(0);
            $table->decimal('tax_amount', 15, 2)->after('tax_percentage')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_percentage', 'tax_amount']);
        });
    }
};