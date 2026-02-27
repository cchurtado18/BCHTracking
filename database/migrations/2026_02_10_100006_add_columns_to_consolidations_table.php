<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            if (!Schema::hasColumn('consolidations', 'code')) {
                $table->string('code', 30)->after('id');
            }
            if (!Schema::hasColumn('consolidations', 'service_type')) {
                $table->string('service_type', 10)->default('AIR')->after('code');
            }
            if (!Schema::hasColumn('consolidations', 'status')) {
                $table->string('status', 20)->default('OPEN')->after('service_type');
            }
            if (!Schema::hasColumn('consolidations', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('consolidations', 'sent_at')) {
                $table->datetime('sent_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consolidations', function (Blueprint $table) {
            $columns = ['code', 'service_type', 'status', 'notes', 'sent_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('consolidations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
