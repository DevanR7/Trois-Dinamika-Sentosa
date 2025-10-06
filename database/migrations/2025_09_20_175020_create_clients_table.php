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
        Schema::create('clients', function (Blueprint $table) {
        $table->id('client_id');
        $table->string('client_name', 150);
        
        // Kolom untuk login, email sekarang wajib dan unik
        $table->string('email', 100)->unique(); 
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        
        // Info kontak
        $table->string('person_in_charge', 100)->nullable();
        $table->text('address')->nullable(); // Dibuat opsional
        $table->string('phone_number', 20)->nullable();
        
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
