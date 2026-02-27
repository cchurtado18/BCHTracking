<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('phone');
            $table->string('department', 100)->nullable()->after('address');
            $table->string('logo_path', 255)->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['address', 'department', 'logo_path']);
        });
    }
};
