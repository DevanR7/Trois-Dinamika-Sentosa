<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_payments', function (Blueprint $table) {
            $table->id('batch_payment_id');
            $table->foreignId('client_id')->constrained('clients', 'client_id');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users', 'user_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            
            // PERBAIKAN: ->after() DIHAPUS
            $table->foreignId('payment_method_id')->nullable()
                  ->constrained('payment_methods', 'payment_method_id')
                  ->onDelete('set null');

            // DITAMBAHKAN DARI add_bank_account_id_to_payment_tables
            $table->foreignId('company_bank_account_id')
                  ->nullable()
                  ->constrained('company_bank_accounts', 'company_bank_account_id')
                  ->nullOnDelete();

            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->json('details')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_payments');
    }
};