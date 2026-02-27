<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renombra la agencia principal "CH Cargo" a "CH LOGISTICS".
     */
    public function up(): void
    {
        DB::table('agencies')
            ->where('is_main', true)
            ->where('name', 'CH Cargo')
            ->update(['name' => 'CH LOGISTICS']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('agencies')
            ->where('is_main', true)
            ->where('name', 'CH LOGISTICS')
            ->update(['name' => 'CH Cargo']);
    }
};
