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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('payment_method_id'); // Primary key
            $table->string('name'); // Contoh: Cash, BCA Transfer, Giro
            $table->enum('type', ['direct', 'pending', 'gateway'])->default('direct');

            // DITAMBAHKAN dari add_required_fields_to_payment_methods_table
            $table->enum('required_fields_config', [
                'none',              // Untuk Cash
                'proof_only',        // Untuk Transfer Bank
                'reference_only',    // Untuk No. Voucher Internal, dsb
                'proof_and_reference'// Untuk Giro (Foto & Nomor)
            ])->default('none');

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
