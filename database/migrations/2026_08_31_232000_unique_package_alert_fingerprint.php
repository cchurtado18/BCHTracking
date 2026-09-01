<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('package_alerts')
            ->select('fingerprint')
            ->groupBy('fingerprint')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('fingerprint');

        foreach ($duplicates as $fingerprint) {
            $keepId = DB::table('package_alerts')
                ->where('fingerprint', $fingerprint)
                ->orderByDesc('id')
                ->value('id');
            DB::table('package_alerts')
                ->where('fingerprint', $fingerprint)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        $indexNames = collect(Schema::getIndexes('package_alerts'))->pluck('name');

        Schema::table('package_alerts', function (Blueprint $table) use ($indexNames) {
            if ($indexNames->contains('package_alerts_fingerprint_index')) {
                $table->dropIndex('package_alerts_fingerprint_index');
            }
        });

        $indexNames = collect(Schema::getIndexes('package_alerts'))->pluck('name');
        if (! $indexNames->contains('package_alerts_fingerprint_unique')) {
            Schema::table('package_alerts', function (Blueprint $table) {
                $table->unique('fingerprint');
            });
        }
    }

    public function down(): void
    {
        $indexNames = collect(Schema::getIndexes('package_alerts'))->pluck('name');
        Schema::table('package_alerts', function (Blueprint $table) use ($indexNames) {
            if ($indexNames->contains('package_alerts_fingerprint_unique')) {
                $table->dropUnique('package_alerts_fingerprint_unique');
            }
        });
    }
};
