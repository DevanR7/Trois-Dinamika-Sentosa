<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_change_requests', function (Blueprint $table) {
            $table->id('request_id'); // Primary key
            $table->foreignId('order_id')->constrained('orders', 'order_id')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients', 'client_id')->cascadeOnDelete();
            $table->enum('request_type', ['cancel', 'modify'])->comment('Jenis permintaan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('client_notes')->nullable()->comment('Catatan dari klien');
            $table->text('admin_notes')->nullable()->comment('Catatan/alasan dari admin');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps(); // created_at (waktu request), updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_change_requests');
    }
};