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
   Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('user_id');
    $table->string('username', 50);
    $table->string('password', 255);
    $table->string('full_name', 100);
    $table->string('email', 255); // Hapus 'after'
    $table->enum('role', ['admin', 'sales', 'manajemen', 'kasir']);
    $table->timestamps();
});

}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
