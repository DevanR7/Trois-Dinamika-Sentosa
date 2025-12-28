<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_client', function (Blueprint $table) {
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->onDelete('cascade');
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id')
                ->onDelete('cascade');
            $table->primary(['announcement_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_client');
    }
};