<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('delivered_by', 200);
            $table->string('delivered_by_id_number', 50)->nullable();
            $table->string('delivered_by_phone', 50)->nullable();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('preregistrations', function (Blueprint $table) {
            $table->foreignId('receipt_note_id')
                ->nullable()
                ->after('agency_client_id')
                ->constrained('receipt_notes')
                ->nullOnDelete();
            $table->index('receipt_note_id');
        });
    }

    public function down(): void
    {
        Schema::table('preregistrations', function (Blueprint $table) {
            $table->dropForeign(['receipt_note_id']);
            $table->dropIndex(['receipt_note_id']);
            $table->dropColumn('receipt_note_id');
        });

        Schema::dropIfExists('receipt_notes');
    }
};
