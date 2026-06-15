<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Limpieza previa: eliminar Delivery duplicados por preregistration_id
        //    Conservamos el de id más bajo (el primero registrado).
        $duplicates = DB::table('deliveries')
            ->select('preregistration_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('preregistration_id')
            ->groupBy('preregistration_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('deliveries')
                ->where('preregistration_id', $row->preregistration_id)
                ->where('id', '>', $row->keep_id)
                ->delete();
        }

        // 2) Aplicar UNIQUE para impedir doble entrega del mismo paquete.
        Schema::table('deliveries', function (Blueprint $table) {
            $table->unique('preregistration_id', 'deliveries_preregistration_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropUnique('deliveries_preregistration_id_unique');
        });
    }
};
