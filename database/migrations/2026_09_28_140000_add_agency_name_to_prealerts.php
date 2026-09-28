<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prealerts', function (Blueprint $table) {
            if (! Schema::hasColumn('prealerts', 'agency_name')) {
                $table->string('agency_name', 120)->nullable()->after('agency_id');
            }
        });

        Schema::table('prealerts', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
        });

        Schema::table('prealerts', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable()->change();
            $table->foreign('agency_id')->references('id')->on('agencies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prealerts', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
        });

        Schema::table('prealerts', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable(false)->change();
            $table->foreign('agency_id')->references('id')->on('agencies')->restrictOnDelete();
            if (Schema::hasColumn('prealerts', 'agency_name')) {
                $table->dropColumn('agency_name');
            }
        });
    }
};
