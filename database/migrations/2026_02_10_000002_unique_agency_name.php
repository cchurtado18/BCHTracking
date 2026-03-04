<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asegurar columnas que la app usa (create_agencies solo tiene id y timestamps)
        Schema::table('agencies', function (Blueprint $table) {
            if (!Schema::hasColumn('agencies', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('agencies', 'code')) {
                $table->string('code', 20)->nullable()->after('name');
            }
            if (!Schema::hasColumn('agencies', 'phone')) {
                $table->string('phone', 50)->nullable()->after('code');
            }
            if (!Schema::hasColumn('agencies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('phone');
            }
        });

        // Añadir unique en name solo si la columna existe
        if (Schema::hasColumn('agencies', 'name')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('agencies', 'name')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        }
    }
};
