<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renombra códigos históricos BCH-#### a SLO-####.
     */
    public function up(): void
    {
        $notes = DB::table('delivery_notes')
            ->where('code', 'like', 'BCH-%')
            ->orderBy('id')
            ->get(['id', 'code']);

        foreach ($notes as $note) {
            $newCode = preg_replace('/^BCH-/', 'SLO-', (string) $note->code);
            if (! is_string($newCode) || $newCode === $note->code) {
                continue;
            }

            $taken = DB::table('delivery_notes')
                ->where('code', $newCode)
                ->where('id', '!=', $note->id)
                ->exists();

            if ($taken) {
                continue;
            }

            DB::table('delivery_notes')->where('id', $note->id)->update(['code' => $newCode]);
        }
    }

    public function down(): void
    {
        $notes = DB::table('delivery_notes')
            ->where('code', 'like', 'SLO-%')
            ->orderBy('id')
            ->get(['id', 'code']);

        foreach ($notes as $note) {
            $newCode = preg_replace('/^SLO-/', 'BCH-', (string) $note->code);
            if (! is_string($newCode) || $newCode === $note->code) {
                continue;
            }

            $taken = DB::table('delivery_notes')
                ->where('code', $newCode)
                ->where('id', '!=', $note->id)
                ->exists();

            if ($taken) {
                continue;
            }

            DB::table('delivery_notes')->where('id', $note->id)->update(['code' => $newCode]);
        }
    }
};
