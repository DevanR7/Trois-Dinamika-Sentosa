<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id('client_id');
            $table->string('google_id')->nullable()->unique();
            $table->string('client_name', 150);
            $table->string('email', 100)->unique()->nullable();
            $table->decimal('credit_balance', 15, 2)->default(0.00);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->rememberToken();
            $table->string('person_in_charge', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};