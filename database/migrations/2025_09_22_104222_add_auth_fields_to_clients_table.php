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
        // Kolom 'email' sudah ada, jadi kita hanya tambahkan sisanya.
        // Metode ->after('email') akan tetap berfungsi karena kolomnya ada di tabel.
        $table->timestamp('email_verified_at')->nullable()->after('email');
        $table->string('password')->after('email_verified_at');
        $table->rememberToken();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            //
        });
    }
};
