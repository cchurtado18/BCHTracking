<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renumera todas las notas de entrega existentes al formato BCH-0001, BCH-0002, ...
     * (orden por id para mantener la secuencia cronológica).
     */
    public function up(): void
    {
        $notes = DB::table('delivery_notes')->orderBy('id')->get();

        foreach ($notes as $index => $note) {
            $newCode = 'BCH-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            DB::table('delivery_notes')->where('id', $note->id)->update(['code' => $newCode]);
        }
    }

    /**
     * No se puede revertir de forma segura (no guardamos los códigos antiguos).
     */
    public function down(): void
    {
        // No-op: los códigos originales no se restauran
    }
};
