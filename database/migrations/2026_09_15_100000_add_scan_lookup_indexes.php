<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            if (! Schema::hasIndex('preregistrations', 'preregistrations_tracking_external_index')) {
                $table->index('tracking_external', 'preregistrations_tracking_external_index');
            }
            if (! Schema::hasIndex('preregistrations', 'preregistrations_warehouse_code_index')) {
                $table->index('warehouse_code', 'preregistrations_warehouse_code_index');
            }
            if (! Schema::hasIndex('preregistrations', 'preregistrations_status_index')) {
                $table->index('status', 'preregistrations_status_index');
            }
        });

        Schema::table('consolidation_items', function (Blueprint $table) {
            if (! Schema::hasIndex('consolidation_items', 'consolidation_items_unmatched_code_index')) {
                $table->index('unmatched_code', 'consolidation_items_unmatched_code_index');
            }
        });

        Schema::table('consolidations', function (Blueprint $table) {
            if (! Schema::hasIndex('consolidations', 'consolidations_code_index')) {
                $table->index('code', 'consolidations_code_index');
            }
            if (! Schema::hasIndex('consolidations', 'consolidations_status_service_index')) {
                $table->index(['status', 'service_type'], 'consolidations_status_service_index');
            }
        });

        DB::table('preregistrations')
            ->whereNotNull('tracking_external')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = strtoupper(preg_replace('/\s+/', '', trim((string) $row->tracking_external)) ?? '');
                    if ($normalized !== (string) $row->tracking_external) {
                        DB::table('preregistrations')->where('id', $row->id)->update([
                            'tracking_external' => $normalized === '' ? null : $normalized,
                        ]);
                    }
                }
            });

        DB::table('consolidation_items')
            ->whereNotNull('unmatched_code')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = strtoupper(preg_replace('/\s+/', '', trim((string) $row->unmatched_code)) ?? '');
                    if ($normalized !== (string) $row->unmatched_code) {
                        DB::table('consolidation_items')->where('id', $row->id)->update([
                            'unmatched_code' => $normalized === '' ? null : $normalized,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $table->dropIndex('preregistrations_tracking_external_index');
            $table->dropIndex('preregistrations_warehouse_code_index');
            $table->dropIndex('preregistrations_status_index');
        });

        Schema::table('consolidation_items', function (Blueprint $table) {
            $table->dropIndex('consolidation_items_unmatched_code_index');
        });

        Schema::table('consolidations', function (Blueprint $table) {
            $table->dropIndex('consolidations_code_index');
            $table->dropIndex('consolidations_status_service_index');
        });
    }
};
