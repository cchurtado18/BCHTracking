<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_invoices', 'delivery_fee_usd')) {
                $table->decimal('delivery_fee_usd', 12, 2)->default(0)->after('total_usd');
            }
        });

        if (! Schema::hasTable('accounting_invoice_delivery_notes')) {
            Schema::create('accounting_invoice_delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accounting_invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
                $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
                $table->timestamps();
                $table->index('delivery_note_id');
                $table->unique(['accounting_invoice_id', 'delivery_note_id'], 'invoice_note_unique_pair');
            });
        }

        $existing = DB::table('accounting_invoices')
            ->whereNotNull('delivery_note_id')
            ->get(['id', 'delivery_note_id', 'created_at', 'updated_at']);

        foreach ($existing as $row) {
            $already = DB::table('accounting_invoice_delivery_notes')
                ->where('delivery_note_id', $row->delivery_note_id)
                ->exists();
            if ($already) {
                continue;
            }
            DB::table('accounting_invoice_delivery_notes')->insert([
                'accounting_invoice_id' => $row->id,
                'delivery_note_id' => $row->delivery_note_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_invoice_delivery_notes');

        Schema::table('accounting_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_invoices', 'delivery_fee_usd')) {
                $table->dropColumn('delivery_fee_usd');
            }
        });
    }
};
