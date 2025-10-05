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
            // Menambahkan status pembayaran, default-nya 'unpaid' (belum lunas)
            $table->enum('payment_status', ['unpaid', 'paid', 'partially_paid'])
                  ->default('unpaid')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
