<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preregistration_photos', function (Blueprint $table) {
            $table->foreignId('preregistration_id')->after('id')->constrained('preregistrations')->cascadeOnDelete();
            $table->string('path')->after('preregistration_id');
            $table->string('mime', 100)->nullable()->after('path');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime');
        });
    }

    public function down(): void
    {
        Schema::table('preregistration_photos', function (Blueprint $table) {
            $table->dropForeign(['preregistration_id']);
            $table->dropColumn(['path', 'mime', 'size_bytes']);
        });
    }
};
