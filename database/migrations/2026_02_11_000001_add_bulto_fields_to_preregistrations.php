<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $table->unsignedTinyInteger('bulto_index')->nullable()->after('dimension')->comment('1-based index when part of a dropoff with multiple bultos');
            $table->unsignedTinyInteger('bultos_total')->nullable()->after('bulto_index')->comment('Total bultos in this dropoff group');
        });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $table->dropColumn(['bulto_index', 'bultos_total']);
        });
    }
};
