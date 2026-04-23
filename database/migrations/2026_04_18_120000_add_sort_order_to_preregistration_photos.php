<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistration_photos', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('size_bytes');
        });

        $rows = DB::table('preregistration_photos')
            ->orderBy('preregistration_id')
            ->orderBy('id')
            ->get(['id', 'preregistration_id']);

        $positionByPrereg = [];
        foreach ($rows as $row) {
            $pid = (int) $row->preregistration_id;
            $positionByPrereg[$pid] = ($positionByPrereg[$pid] ?? 0);
            DB::table('preregistration_photos')->where('id', $row->id)->update([
                'sort_order' => $positionByPrereg[$pid],
            ]);
            $positionByPrereg[$pid]++;
        }
    }

    public function down(): void
    {
        Schema::table('preregistration_photos', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
