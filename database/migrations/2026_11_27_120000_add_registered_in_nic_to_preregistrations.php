<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('preregistrations', 'registered_in_nic')) {
            Schema::table('preregistrations', function (Blueprint $table) {
                $table->boolean('registered_in_nic')->default(false)->after('received_nic_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('preregistrations', 'registered_in_nic')) {
            Schema::table('preregistrations', function (Blueprint $table) {
                $table->dropColumn('registered_in_nic');
            });
        }
    }
};
