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
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->foreignId('chart_of_account_id')
                ->nullable()
                ->constrained('chart_of_accounts', 'account_id')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};