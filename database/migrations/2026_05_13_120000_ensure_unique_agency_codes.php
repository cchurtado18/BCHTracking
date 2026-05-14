<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asigna códigos numéricos únicos por fila en agencies.
 * - Mantiene 0001 / 0002 para las agencias principales SkyLink One y CH LOGISTICS (por nombre).
 * - Corrige NULL, vacíos, no numéricos y duplicados sin tocar relaciones (solo columna code).
 * - Añade índice único en code para evitar duplicados futuros.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agencies') || ! Schema::hasColumn('agencies', 'code')) {
            return;
        }

        $now = now();

        if (Schema::hasColumn('agencies', 'is_main')) {
            $skylinkMain = DB::table('agencies')
                ->where('is_main', true)
                ->where('name', 'SkyLink One')
                ->orderBy('id')
                ->first();
            if ($skylinkMain) {
                DB::table('agencies')->where('id', $skylinkMain->id)->update(['code' => '0001', 'updated_at' => $now]);
            }

            $chMain = DB::table('agencies')
                ->where('is_main', true)
                ->whereIn('name', ['CH LOGISTICS', 'CH Cargo'])
                ->orderBy('id')
                ->first();
            if ($chMain) {
                DB::table('agencies')->where('id', $chMain->id)->update(['code' => '0002', 'updated_at' => $now]);
            }
        }

        $rows = Schema::hasColumn('agencies', 'is_main')
            ? DB::table('agencies')->orderByDesc('is_main')->orderBy('id')->get()
            : DB::table('agencies')->orderBy('id')->get();

        $codeOwner = [];
        $needReassign = [];

        foreach ($rows as $a) {
            $raw = trim((string) ($a->code ?? ''));
            if ($raw === '' || ! preg_match('/^\d+$/', $raw)) {
                $needReassign[] = (int) $a->id;

                continue;
            }
            $num = (int) $raw;
            if (! isset($codeOwner[$num])) {
                $codeOwner[$num] = (int) $a->id;
            } elseif ($codeOwner[$num] !== (int) $a->id) {
                $needReassign[] = (int) $a->id;
            }
        }

        $needReassign = array_values(array_unique($needReassign));
        sort($needReassign, SORT_NUMERIC);

        $next = $codeOwner === [] ? 1 : max(array_keys($codeOwner)) + 1;
        foreach ($needReassign as $id) {
            while (isset($codeOwner[$next])) {
                $next++;
            }
            $newCode = $this->formatAgencyCode($next);
            DB::table('agencies')->where('id', $id)->update(['code' => $newCode, 'updated_at' => $now]);
            $codeOwner[$next] = $id;
            $next++;
        }

        if (! Schema::hasIndex('agencies', ['code'], 'unique')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->unique('code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('agencies')) {
            return;
        }
        if (Schema::hasIndex('agencies', ['code'], 'unique')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        }
    }

    private function formatAgencyCode(int $n): string
    {
        if ($n <= 9999) {
            return str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }

        return (string) $n;
    }
};
