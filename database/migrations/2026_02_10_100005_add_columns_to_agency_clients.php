<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_clients', function (Blueprint $table) {
            $table->foreignId('agency_id')->after('id')->constrained('agencies')->cascadeOnDelete();
            $table->string('full_name')->after('agency_id');
            $table->string('phone', 50)->nullable()->after('full_name');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('agency_clients', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['full_name', 'phone', 'is_active']);
        });
    }
};
