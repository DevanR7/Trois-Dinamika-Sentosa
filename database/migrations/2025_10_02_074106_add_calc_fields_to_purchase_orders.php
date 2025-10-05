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
            // optional link to taxes table (assume taxes.id PK)
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('total_amount');

            // disc/fee options
            $table->boolean('apply_disc_fee')->default(false)->after('tax_id');
            $table->decimal('disc_fee_percent', 5, 2)->nullable()->after('apply_disc_fee'); // 0-100 with 2 dec
            $table->decimal('disc_fee_amount', 15, 2)->nullable()->after('disc_fee_percent'); // fixed amount

            // rounding discount
            $table->boolean('apply_rounding_discount')->default(false)->after('disc_fee_amount');
            $table->decimal('rounding_discount_amount', 15, 2)->nullable()->after('apply_rounding_discount');

            // custom DPP factor (default 11/12)
            $table->boolean('use_custom_dpp_factor')->default(false)->after('rounding_discount_amount');
            $table->decimal('custom_dpp_factor', 12, 8)->nullable()->after('use_custom_dpp_factor');

            // shipping cost
            $table->decimal('shipping_amount', 15, 2)->default(0)->after('custom_dpp_factor');

            // store computed values
            $table->decimal('dpp', 15, 2)->nullable()->after('shipping_amount');
            $table->decimal('ppn', 15, 2)->nullable()->after('dpp');
            $table->decimal('grand_total', 15, 2)->nullable()->after('ppn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('purchase_orders', function (Blueprint $table) {
            // drop foreign key first if exists
            if (Schema::hasColumn('purchase_orders', 'tax_id')) {
                $table->dropConstrainedForeignId('tax_id');
            }
            $table->dropColumn([
                'apply_disc_fee',
                'disc_fee_percent',
                'disc_fee_amount',
                'apply_rounding_discount',
                'rounding_discount_amount',
                'use_custom_dpp_factor',
                'custom_dpp_factor',
                'shipping_amount',
                'dpp',
                'ppn',
                'grand_total',
            ]);
        });
    }
};
