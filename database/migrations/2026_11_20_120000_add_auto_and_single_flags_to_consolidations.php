<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            if (! Schema::hasColumn('consolidations', 'is_auto')) {
                $table->boolean('is_auto')->default(false)->after('status');
            }
            if (! Schema::hasColumn('consolidations', 'is_single')) {
                $table->boolean('is_single')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            foreach (['is_auto', 'is_single'] as $column) {
                if (Schema::hasColumn('consolidations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
