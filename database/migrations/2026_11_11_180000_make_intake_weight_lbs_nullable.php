<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('preregistrations', 'intake_weight_lbs')) {
            return;
        }

        Schema::table('preregistrations', function (Blueprint $table) {
            $table->decimal('intake_weight_lbs', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('preregistrations', 'intake_weight_lbs')) {
            return;
        }

        Schema::table('preregistrations', function (Blueprint $table) {
            $table->decimal('intake_weight_lbs', 12, 2)->default(0)->nullable(false)->change();
        });
    }
};
