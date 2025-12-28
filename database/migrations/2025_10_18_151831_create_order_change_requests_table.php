<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_change_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('order_id')
                ->constrained('orders', 'order_id')
                ->cascadeOnDelete();
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id')
                ->cascadeOnDelete();
            $table->enum('request_type', ['cancel', 'modify']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('client_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by_user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('order_change_requests');
    }
};