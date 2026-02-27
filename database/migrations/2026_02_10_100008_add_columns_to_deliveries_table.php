<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('preregistration_id')->after('id')->constrained('preregistrations')->cascadeOnDelete();
            $table->dateTime('delivered_at')->nullable()->after('preregistration_id');
            $table->string('delivered_to', 255)->nullable()->after('delivered_at');
            $table->string('delivery_type', 50)->nullable()->after('delivered_to');
            $table->text('notes')->nullable()->after('delivery_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['preregistration_id']);
            $table->dropColumn(['preregistration_id', 'delivered_at', 'delivered_to', 'delivery_type', 'notes']);
        });
    }
};
