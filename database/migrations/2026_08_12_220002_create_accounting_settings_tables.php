<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PrimeTrack Group');
            $table->string('company_tax_id', 50)->nullable();
            $table->string('company_address', 500)->nullable();
            $table->string('company_phones', 120)->nullable();
            $table->string('voucher_footer')->default('Es un gusto atenderle!');
            $table->string('folio_prefix', 16)->default('FP-');
            $table->decimal('exchange_rate', 12, 4)->default(36.6243);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('accounting_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 12, 4);
            $table->date('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('effective_from');
        });

        $now = now();
        $rate = (float) env('ACCOUNTING_EXCHANGE_RATE', 36.6243);

        DB::table('accounting_settings')->insert([
            'company_name' => env('ACCOUNTING_COMPANY_NAME', 'PrimeTrack Group'),
            'company_tax_id' => env('ACCOUNTING_COMPANY_TAX_ID') ?: null,
            'company_address' => env('ACCOUNTING_COMPANY_ADDRESS') ?: null,
            'company_phones' => env('ACCOUNTING_COMPANY_PHONES') ?: null,
            'voucher_footer' => env('ACCOUNTING_VOUCHER_FOOTER', 'Es un gusto atenderle!'),
            'folio_prefix' => env('ACCOUNTING_FOLIO_PREFIX', 'FP-'),
            'exchange_rate' => $rate,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('accounting_exchange_rates')->insert([
            'rate' => $rate,
            'effective_from' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_exchange_rates');
        Schema::dropIfExists('accounting_settings');
    }
};
