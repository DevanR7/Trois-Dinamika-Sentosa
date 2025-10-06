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
        // Cukup definisikan tipe dan properti barunya (nullable)
        // Aturan 'unique' yang sudah ada tidak akan terpengaruh
        $table->string('email', 100)->nullable()->change();
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
