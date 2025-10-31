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
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('debit_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('credit_balance', 15, 2)->default(0.00)->after('email');
        });
        
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('debit_balance', 15, 2)->default(0.00)->after('account_number');
        });
    }
};