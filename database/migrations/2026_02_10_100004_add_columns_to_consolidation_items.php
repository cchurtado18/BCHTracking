<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->foreignId('consolidation_id')->after('id')->constrained('consolidations')->cascadeOnDelete();
            $table->foreignId('preregistration_id')->after('consolidation_id')->constrained('preregistrations')->cascadeOnDelete();
            $table->datetime('scanned_at')->nullable()->after('preregistration_id');
        });
    }

    public function down(): void
    {
        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->dropForeign(['consolidation_id']);
            $table->dropForeign(['preregistration_id']);
            $table->dropColumn('scanned_at');
        });
    }
};
