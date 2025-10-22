<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('google_id')->nullable()->unique();
            $table->string('username', 50)->unique();
            $table->string('password', 255)->nullable(); // Dibuat nullable untuk login via Google
            $table->boolean('is_approved')->default(false); 
            $table->string('full_name', 100);
            $table->string('email', 255)->unique();
            $table->string('sales_code', 10)->nullable()->unique();
            $table->string('nik', 20)->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};