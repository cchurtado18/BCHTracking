<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->restrictOnDelete();
            $table->decimal('amount_usd', 14, 2);
            $table->date('paid_at');
            $table->string('method', 32)->default('cash');
            $table->string('reference', 120)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('status', 16)->default('active'); // active | void
            $table->string('void_reason', 500)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'status', 'paid_at']);
        });

        Schema::create('accounting_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('accounting_payments')->cascadeOnDelete();
            $table->foreignId('accounting_invoice_id')->constrained('accounting_invoices')->restrictOnDelete();
            $table->decimal('amount_usd', 14, 2);
            $table->timestamps();

            $table->index('accounting_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payment_allocations');
        Schema::dropIfExists('accounting_payments');
    }
};
