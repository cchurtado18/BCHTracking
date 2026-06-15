<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('invoice_number', 50)->nullable()->after('retirer_phone');
            $table->index('invoice_number', 'deliveries_invoice_number_index');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('deliveries_invoice_number_index');
            $table->dropColumn('invoice_number');
        });
    }
};
