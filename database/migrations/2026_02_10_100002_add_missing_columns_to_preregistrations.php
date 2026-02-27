<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            if (!Schema::hasColumn('preregistrations', 'intake_type')) {
                $table->string('intake_type', 20)->default('COURIER')->after('id');
            }
            if (!Schema::hasColumn('preregistrations', 'agency_id')) {
                $table->foreignId('agency_id')->nullable()->after('intake_type')->constrained('agencies')->nullOnDelete();
            }
            if (!Schema::hasColumn('preregistrations', 'tracking_external')) {
                $table->string('tracking_external')->nullable()->after('agency_id');
            }
            if (!Schema::hasColumn('preregistrations', 'warehouse_code')) {
                $table->string('warehouse_code', 20)->nullable()->after('tracking_external');
            }
            if (!Schema::hasColumn('preregistrations', 'label_name')) {
                $table->string('label_name')->after('warehouse_code');
            }
            if (!Schema::hasColumn('preregistrations', 'service_type')) {
                $table->string('service_type', 10)->default('AIR')->after('label_name');
            }
            if (!Schema::hasColumn('preregistrations', 'intake_weight_lbs')) {
                $table->decimal('intake_weight_lbs', 12, 2)->default(0)->after('service_type');
            }
            if (!Schema::hasColumn('preregistrations', 'status')) {
                $table->string('status', 50)->default('RECEIVED_MIAMI')->after('dimension');
            }
        });

        Schema::table('preregistrations', function (Blueprint $table) {
            if (!Schema::hasColumn('preregistrations', 'agency_client_id')) {
                $table->foreignId('agency_client_id')->nullable()->after('agency_id')->constrained('agency_clients')->nullOnDelete();
            }
        });

        Schema::table('preregistrations', function (Blueprint $table) {
            if (!Schema::hasColumn('preregistrations', 'assignment_status')) {
                $table->string('assignment_status', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('preregistrations', 'holding_reason')) {
                $table->string('holding_reason')->nullable()->after('assignment_status');
            }
            if (!Schema::hasColumn('preregistrations', 'received_nic_at')) {
                $table->datetime('received_nic_at')->nullable()->after('holding_reason');
            }
            if (!Schema::hasColumn('preregistrations', 'verified_weight_lbs')) {
                $table->decimal('verified_weight_lbs', 12, 2)->nullable()->after('received_nic_at');
            }
            if (!Schema::hasColumn('preregistrations', 'ready_at')) {
                $table->datetime('ready_at')->nullable()->after('verified_weight_lbs');
            }
            if (!Schema::hasColumn('preregistrations', 'label_print_count')) {
                $table->unsignedInteger('label_print_count')->default(0)->after('ready_at');
            }
            if (!Schema::hasColumn('preregistrations', 'label_last_printed_at')) {
                $table->datetime('label_last_printed_at')->nullable()->after('label_print_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $cols = ['label_last_printed_at', 'label_print_count', 'ready_at', 'verified_weight_lbs', 'received_nic_at', 'holding_reason', 'assignment_status', 'agency_client_id', 'status', 'intake_weight_lbs', 'service_type', 'label_name', 'warehouse_code', 'tracking_external', 'agency_id', 'intake_type'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('preregistrations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
