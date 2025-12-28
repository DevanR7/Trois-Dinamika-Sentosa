<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('supplier_name', 150);
            $table->string('person_in_charge', 100)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('bank_name', 50)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};