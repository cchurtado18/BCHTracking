<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule', 32);
            $table->string('fingerprint', 80);
            $table->foreignId('preregistration_id')->constrained('preregistrations')->cascadeOnDelete();
            $table->string('status_at_open', 40)->nullable();
            $table->text('message');
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['resolved_at', 'rule']);
            $table->unique('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_alerts');
    }
};
