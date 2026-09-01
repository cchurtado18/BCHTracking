<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 32)->unique();
            $table->foreignId('delivery_note_id')->nullable()->constrained('delivery_notes')->nullOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->restrictOnDelete();
            $table->string('status', 32)->default('issued'); // draft|issued|partially_paid|paid|void
            $table->date('issued_at')->nullable();
            $table->decimal('total_lbs', 12, 3)->default(0);
            $table->decimal('total_usd', 14, 2)->default(0);
            $table->decimal('total_cor', 14, 2)->default(0);
            $table->decimal('exchange_rate', 12, 4)->default(1); // COR por 1 USD
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status', 'issued_at']);
            $table->index('delivery_note_id');
        });

        Schema::create('accounting_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
            $table->foreignId('preregistration_id')->nullable()->constrained('preregistrations')->nullOnDelete();
            $table->string('service_type', 10); // AIR | SEA | OTHER
            $table->string('description');
            $table->decimal('quantity_lbs', 12, 3)->default(0);
            $table->decimal('rate_per_lb', 12, 4)->default(0);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['accounting_invoice_id', 'service_type'], 'ail_invoice_svc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_invoice_lines');
        Schema::dropIfExists('accounting_invoices');
    }
};
