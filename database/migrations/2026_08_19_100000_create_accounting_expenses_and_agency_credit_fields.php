<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('accounting_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('accounting_expense_categories')->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->decimal('amount_usd', 14, 2);
            $table->date('spent_at');
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['spent_at', 'category_id']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('credit_limit_usd', 14, 2)->nullable()->after('account_type');
            $table->unsignedSmallInteger('credit_days')->nullable()->after('credit_limit_usd');
            $table->string('tax_id', 50)->nullable()->after('credit_days');
            $table->string('billing_contact_name', 120)->nullable()->after('tax_id');
            $table->string('billing_contact_phone', 40)->nullable()->after('billing_contact_name');
        });

        $now = now();
        DB::table('accounting_expense_categories')->insert(
            collect(['Flete internacional', 'Combustible', 'Planilla', 'Renta local', 'Servicios', 'Otros'])
                ->map(fn ($name) => ['name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['credit_limit_usd', 'credit_days', 'tax_id', 'billing_contact_name', 'billing_contact_phone']);
        });
        Schema::dropIfExists('accounting_expenses');
        Schema::dropIfExists('accounting_expense_categories');
    }
};
