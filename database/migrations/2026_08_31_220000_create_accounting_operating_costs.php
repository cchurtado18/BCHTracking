<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_operating_costs', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 10);
            $table->decimal('cost_per_unit', 12, 4);
            $table->date('effective_from');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->decimal('amount_paid_usd', 14, 2)->nullable();
            $table->decimal('quantity', 14, 4)->nullable();
            $table->string('quantity_unit', 8)->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_type', 'effective_from']);
        });

        Schema::table('accounting_expenses', function (Blueprint $table) {
            $table->string('service_type', 10)->nullable()->after('agency_id');
            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_expenses', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });
        Schema::dropIfExists('accounting_operating_costs');
    }
};
