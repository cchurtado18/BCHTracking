<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            if (! Schema::hasColumn('preregistrations', 'miami_received_notified_at')) {
                $table->timestamp('miami_received_notified_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('preregistrations', 'ready_notified_at')) {
                $table->timestamp('ready_notified_at')->nullable()->after('ready_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            if (Schema::hasColumn('preregistrations', 'miami_received_notified_at')) {
                $table->dropColumn('miami_received_notified_at');
            }
            if (Schema::hasColumn('preregistrations', 'ready_notified_at')) {
                $table->dropColumn('ready_notified_at');
            }
        });
    }
};
