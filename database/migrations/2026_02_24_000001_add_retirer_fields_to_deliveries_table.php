<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('retirer_id_number', 50)->nullable()->after('delivered_to');
            $table->string('retirer_phone', 50)->nullable()->after('retirer_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['retirer_id_number', 'retirer_phone']);
        });
    }
};
