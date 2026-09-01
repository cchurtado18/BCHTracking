<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('account_type', 32)->default('subagency')->after('is_main');
        });

        $now = now();

        $slo = DB::table('agencies')
            ->where(function ($q) {
                $q->where('code', '0001')->orWhere('name', 'SkyLink One');
            })
            ->orderBy('id')
            ->first();

        $ch = DB::table('agencies')
            ->where(function ($q) {
                $q->where('code', '0002')->orWhere('name', 'CH LOGISTICS');
            })
            ->orderBy('id')
            ->first();

        if ($slo) {
            DB::table('agencies')->where('id', $slo->id)->update([
                'is_main' => true,
                'parent_agency_id' => null,
                'account_type' => 'root',
                'updated_at' => $now,
            ]);
        }

        if ($ch && $slo && (int) $ch->id !== (int) $slo->id) {
            DB::table('agencies')->where('id', $ch->id)->update([
                'is_main' => false,
                'parent_agency_id' => $slo->id,
                'account_type' => 'subagency',
                'updated_at' => $now,
            ]);
        }

        if ($slo) {
            DB::table('agencies')->where('id', '!=', $slo->id)->where('is_main', true)->update([
                'is_main' => false,
                'account_type' => 'subagency',
                'updated_at' => $now,
            ]);
        }

        DB::table('agencies')
            ->where('is_main', false)
            ->where('account_type', 'root')
            ->update(['account_type' => 'subagency', 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
