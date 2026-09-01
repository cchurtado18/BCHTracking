<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('service_type', 10); // AIR | SEA
            $table->decimal('price_per_lb', 12, 4);
            $table->decimal('cost_per_lb', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'service_type', 'effective_from'], 'arc_agency_svc_from_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_rate_cards');
    }
};
