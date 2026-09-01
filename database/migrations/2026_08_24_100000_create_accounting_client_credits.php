<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('credit_balance_usd', 14, 2)->default(0)->after('credit_days');
        });

        Schema::create('accounting_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 32)->unique();
            $table->foreignId('agency_id')->constrained('agencies')->restrictOnDelete();
            $table->decimal('amount_usd', 14, 2);
            $table->string('reason', 500);
            $table->string('status', 16)->default('active');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
        });

        Schema::create('accounting_credit_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->restrictOnDelete();
            $table->decimal('amount_usd', 14, 2);
            $table->string('type', 32);
            $table->foreignId('payment_id')->nullable()->constrained('accounting_payments')->nullOnDelete();
            $table->foreignId('credit_note_id')->nullable()->constrained('accounting_credit_notes')->nullOnDelete();
            $table->foreignId('accounting_invoice_id')->nullable()->constrained('accounting_invoices')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'type']);
            $table->index('accounting_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_credit_movements');
        Schema::dropIfExists('accounting_credit_notes');
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('credit_balance_usd');
        });
    }
};
