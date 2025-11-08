<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id('company_bank_account_id');
            $table->string('bank_name'); // Misal: "BCA", "Mandiri", "Kas Tunai"
            $table->string('account_name'); // Misal: "PT. USAHA JAYA"
            $table->string('account_number')->nullable(); // Misal: "1234567890" (nullable untuk kas)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};