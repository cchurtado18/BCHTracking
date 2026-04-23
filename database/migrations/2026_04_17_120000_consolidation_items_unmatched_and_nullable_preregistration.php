<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->dropForeign(['preregistration_id']);
        });

        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->unsignedBigInteger('preregistration_id')->nullable()->change();
            $table->foreign('preregistration_id')->references('id')->on('preregistrations')->cascadeOnDelete();
            $table->string('unmatched_code', 191)->nullable()->after('preregistration_id');
        });
    }

    public function down(): void
    {
        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->dropForeign(['preregistration_id']);
            $table->dropColumn('unmatched_code');
        });

        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->unsignedBigInteger('preregistration_id')->nullable(false)->change();
            $table->foreign('preregistration_id')->references('id')->on('preregistrations')->cascadeOnDelete();
        });
    }
};
