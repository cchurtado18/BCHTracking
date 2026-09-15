<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prealerts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('agency_id')->constrained('agencies')->restrictOnDelete();
            $table->string('tracking');
            $table->string('service_type', 16)->default('AIR');
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('preregistration_id')->nullable()->constrained('preregistrations')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique('tracking');
            $table->index(['agency_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prealerts');
    }
};
