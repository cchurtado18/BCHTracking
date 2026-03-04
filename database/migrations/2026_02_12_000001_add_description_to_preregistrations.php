<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            if (Schema::hasColumn('preregistrations', 'description')) {
                return;
            }
            if (Schema::hasColumn('preregistrations', 'dimension')) {
                $table->string('description', 500)->nullable()->after('dimension');
            } else {
                $table->string('description', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
