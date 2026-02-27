<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_sequences', function (Blueprint $table) {
            $table->unsignedInteger('next_number')->default(1)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_sequences', function (Blueprint $table) {
            $table->dropColumn('next_number');
        });
    }
};
