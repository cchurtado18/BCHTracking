<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade parent_agency_id (subagencias) e is_main (solo las 2 agencias principales).
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreignId('parent_agency_id')->nullable()->after('id')->constrained('agencies')->nullOnDelete();
            $table->boolean('is_main')->default(false)->after('parent_agency_id');
        });

        $now = now();
        foreach (['SkyLink One' => '0001', 'CH LOGISTICS' => '0002'] as $name => $code) {
            $exists = DB::table('agencies')->where('name', $name)->first();
            if ($exists) {
                DB::table('agencies')->where('id', $exists->id)->update(['is_main' => true]);
            } else {
                DB::table('agencies')->insert([
                    'parent_agency_id' => null,
                    'is_main' => true,
                    'code' => $code,
                    'name' => $name,
                    'phone' => null,
                    'address' => null,
                    'department' => null,
                    'logo_path' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropForeign(['parent_agency_id']);
            $table->dropColumn(['parent_agency_id', 'is_main']);
        });
    }
};
