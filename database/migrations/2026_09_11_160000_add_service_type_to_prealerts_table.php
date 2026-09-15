<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prealerts', function (Blueprint $table) {
            if (! Schema::hasColumn('prealerts', 'service_type')) {
                $table->string('service_type', 16)->default('AIR')->after('tracking');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prealerts', function (Blueprint $table) {
            if (Schema::hasColumn('prealerts', 'service_type')) {
                $table->dropColumn('service_type');
            }
        });
    }
};
