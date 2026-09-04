<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            if (! Schema::hasColumn('consolidations', 'transport_number')) {
                $table->string('transport_number', 80)->nullable()->after('service_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            if (Schema::hasColumn('consolidations', 'transport_number')) {
                $table->dropColumn('transport_number');
            }
        });
    }
};
