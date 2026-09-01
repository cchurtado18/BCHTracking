<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'billing_email')) {
                $table->string('billing_email', 255)->nullable()->after('billing_contact_phone');
            }
        });

        Schema::table('accounting_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_invoices', 'emailed_at')) {
                $table->timestamp('emailed_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'billing_email')) {
                $table->dropColumn('billing_email');
            }
        });

        Schema::table('accounting_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_invoices', 'emailed_at')) {
                $table->dropColumn('emailed_at');
            }
        });
    }
};
