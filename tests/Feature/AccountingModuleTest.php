<?php

namespace Tests\Feature;

use App\Models\AccountingCreditNote;
use App\Models\AccountingExchangeRate;
use App\Models\AccountingInvoice;
use App\Models\AccountingOperatingCost;
use App\Models\AccountingPayment;
use App\Models\AccountingRateCard;
use App\Models\AccountingSetting;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['agency_id' => null, 'is_admin' => true]);
    }

    private function agency(string $code = 'A901'): Agency
    {
        return Agency::create([
            'name' => 'Agencia Contable '.$code,
            'code' => $code,
            'phone' => '2222-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function issuedInvoice(Agency $agency, float $total = 100.0): AccountingInvoice
    {
        return AccountingInvoice::create([
            'folio' => AccountingInvoice::generateFolio(),
            'agency_id' => $agency->id,
            'status' => 'issued',
            'issued_at' => now(),
            'total_lbs' => 10,
            'total_usd' => $total,
            'exchange_rate' => 36.5,
            'total_nio' => $total * 36.5,
            'amount_paid' => 0,
        ]);
    }

    public function test_registering_new_rate_closes_previous_and_keeps_history(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        $this->actingAs($admin)->post(route('accounting.rates.store'), [
            'agency_id' => $agency->id,
            'price_air' => 3.50,
            'effective_from' => now()->subDays(10)->toDateString(),
        ])->assertRedirect(route('accounting.rates.show', $agency));

        $this->actingAs($admin)->post(route('accounting.rates.store'), [
            'agency_id' => $agency->id,
            'price_air' => 4.00,
            'effective_from' => now()->toDateString(),
        ])->assertRedirect(route('accounting.rates.show', $agency));

        $this->assertSame(2, AccountingRateCard::count());

        $current = AccountingRateCard::currentFor($agency->id, 'AIR');
        $this->assertNotNull($current);
        $this->assertEquals(4.00, (float) $current->price_per_lb);
        $this->assertEquals(0.0, (float) $current->cost_per_lb);

        $previous = AccountingRateCard::query()->whereNotNull('effective_to')->first();
        $this->assertNotNull($previous);
        $this->assertEquals(3.50, (float) $previous->price_per_lb);

        $this->actingAs($admin)
            ->get(route('accounting.rates.history', ['agency_id' => $agency->id]))
            ->assertOk()
            ->assertSee('Vigente')
            ->assertSee('Histórica')
            ->assertDontSee('Costo / lb');
    }

    public function test_rates_index_lists_clients_and_show_asks_only_sale_price(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        $this->actingAs($admin)
            ->get(route('accounting.rates.index'))
            ->assertOk()
            ->assertSee($agency->name)
            ->assertSee('Definir precios')
            ->assertDontSee('Costo interno');

        $this->actingAs($admin)
            ->get(route('accounting.rates.show', $agency))
            ->assertOk()
            ->assertSee('Aéreo')
            ->assertSee('Marítimo')
            ->assertSee('Pie cúbico')
            ->assertSee('USD / pie³')
            ->assertDontSee('Costo interno');
    }

    public function test_settings_update_persists_and_records_exchange_rate_history(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('accounting.settings.edit'))
            ->assertOk()
            ->assertSee('Tipo de cambio')
            ->assertSee('Costo de operación')
            ->assertSee('Aéreo')
            ->assertSee('Marítimo')
            ->assertSee('Pie cúbico')
            ->assertDontSee('Empresa (voucher')
            ->assertDontSee('Prefijo de folio')
            ->assertDontSee('Fletes del período');

        $this->actingAs($admin)->put(route('accounting.settings.update'), [
            'exchange_rate' => 37.1234,
        ])->assertRedirect(route('accounting.settings.edit'));

        $settings = AccountingSetting::current();
        $this->assertEquals(37.1234, (float) $settings->exchange_rate);

        $this->assertDatabaseHas('accounting_exchange_rates', ['rate' => 37.1234]);
        $this->assertTrue(AccountingExchangeRate::query()->where('rate', 37.1234)->exists());

        $this->actingAs($admin)
            ->get(route('accounting.settings.edit'))
            ->assertOk()
            ->assertSee('Cambios')
            ->assertSee('37.1234');
    }

    public function test_operating_cost_is_saved_from_parameters(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('accounting.settings.update'), [
            'exchange_rate' => AccountingSetting::current()->exchange_rate,
            'cost_air' => 3.5,
            'cost_sea' => 1.25,
            'cost_cft' => 8,
        ])->assertRedirect(route('accounting.settings.edit'));

        $air = AccountingOperatingCost::currentFor('AIR');
        $this->assertNotNull($air);
        $this->assertEquals(3.5, (float) $air->cost_per_unit);

        $this->assertEquals(1.25, (float) AccountingOperatingCost::currentFor('SEA')->cost_per_unit);
        $this->assertEquals(8.0, (float) AccountingOperatingCost::currentFor('CFT')->cost_per_unit);

        $this->actingAs($admin)
            ->get(route('accounting.settings.edit'))
            ->assertOk()
            ->assertSee('Cambios')
            ->assertSee('3.5000');
    }

    public function test_partial_payment_then_full_payment_updates_invoice_status(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 100.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$invoice->id => 40],
        ])->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEquals(40.0, (float) $invoice->amount_paid);
        $this->assertEquals(60.0, $invoice->balanceUsd());

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'reference' => 'TX-100',
            'allocations' => [$invoice->id => 60],
        ])->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(0.0, $invoice->balanceUsd());
        $this->assertSame(2, AccountingPayment::count());
    }

    public function test_overpayment_marks_invoice_paid_and_credits_the_excess(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 50.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$invoice->id => 80],
        ])->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $agency->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(50.0, (float) $invoice->amount_paid);
        $this->assertEquals(30.0, (float) $agency->credit_balance_usd);
        $this->assertEquals(80.0, (float) AccountingPayment::first()->amount_usd);
    }

    public function test_available_credit_can_be_applied_on_a_later_payment(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $first = $this->issuedInvoice($agency, 50.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$first->id => 80],
        ])->assertRedirect();

        $second = $this->issuedInvoice($agency, 80.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$second->id => 50],
            'apply_credit' => 30,
        ])->assertRedirect(route('accounting.payments.index'));

        $second->refresh();
        $agency->refresh();
        $this->assertSame('paid', $second->status);
        $this->assertEquals(80.0, (float) $second->amount_paid);
        $this->assertEquals(0.0, (float) $agency->credit_balance_usd);
    }

    public function test_credit_note_increases_client_credit_balance(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        $this->actingAs($admin)
            ->get(route('accounting.credit-notes.create'))
            ->assertOk()
            ->assertSee('Nueva nota de crédito');

        $this->actingAs($admin)->post(route('accounting.credit-notes.store'), [
            'agency_id' => $agency->id,
            'amount_usd' => 40,
            'reason' => 'Ajuste por reclamo de peso',
        ])->assertRedirect();

        $note = AccountingCreditNote::first();
        $this->assertNotNull($note);
        $this->assertSame('NC-0001', $note->folio);
        $this->assertEquals(40.0, (float) $agency->fresh()->credit_balance_usd);
        $this->assertEquals(40.0, $note->remainingUsd());

        $this->actingAs($admin)
            ->get(route('accounting.credit-notes.show', $note))
            ->assertOk()
            ->assertSee('NC-0001')
            ->assertSee('Restante')
            ->assertSee('40.00')
            ->assertSee('aún no se ha aplicado');

        $this->actingAs($admin)
            ->get(route('accounting.credit-notes.index'))
            ->assertOk()
            ->assertSee('NC-0001')
            ->assertSee('Saldo a favor total')
            ->assertSee('Restante');

        $this->actingAs($admin)
            ->get(route('accounting.receivables.index'))
            ->assertOk()
            ->assertSee('Saldo a favor')
            ->assertSee('40.00');

        $this->actingAs($admin)
            ->get(route('accounting.receivables.show', $agency))
            ->assertOk()
            ->assertSee('NC-0001')
            ->assertSee('Movimientos de saldo a favor')
            ->assertSee('Ajuste por reclamo de peso');
    }

    public function test_credit_note_detail_shows_remaining_and_where_it_was_applied(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        $this->actingAs($admin)->post(route('accounting.credit-notes.store'), [
            'agency_id' => $agency->id,
            'amount_usd' => 40,
            'reason' => 'Saldo a favor para aplicar por partes',
        ])->assertRedirect();

        $note = AccountingCreditNote::first();
        $invoice = $this->issuedInvoice($agency, 25.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'apply_credit' => 25,
            'invoice_id' => $invoice->id,
            'amount' => 0,
        ])->assertRedirect();

        $note->refresh()->load('movements.invoice');
        $this->assertEquals(25.0, $note->appliedUsd());
        $this->assertEquals(15.0, $note->remainingUsd());
        $this->assertSame('partial', $note->usageStatus());

        $this->actingAs($admin)
            ->get(route('accounting.credit-notes.show', $note))
            ->assertOk()
            ->assertSee($invoice->folio)
            ->assertSee('25.00')
            ->assertSee('15.00')
            ->assertSee('Dónde se aplicó')
            ->assertSee('Parcial');

        $this->actingAs($admin)
            ->get(route('accounting.credit-notes.index'))
            ->assertOk()
            ->assertSee('15.00')
            ->assertSee('Parcial');

        $this->actingAs($admin)
            ->get(route('accounting.receivables.show', $agency))
            ->assertOk()
            ->assertSee('15.00');
    }

    public function test_cannot_void_payment_or_credit_note_after_credit_was_applied(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $first = $this->issuedInvoice($agency, 50.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$first->id => 80],
        ])->assertRedirect();

        $overpay = AccountingPayment::first();
        $second = $this->issuedInvoice($agency, 30.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'apply_credit' => 30,
            'invoice_id' => $second->id,
            'amount' => 0,
        ])->assertRedirect();

        $second->refresh();
        $this->assertSame('paid', $second->status);

        $this->actingAs($admin)
            ->post(route('accounting.payments.void', $overpay), [
                'void_reason' => 'Se registró por error',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $overpay->refresh();
        $this->assertSame('active', $overpay->status);

        $this->actingAs($admin)->post(route('accounting.credit-notes.store'), [
            'agency_id' => $agency->id,
            'amount_usd' => 25,
            'reason' => 'Nota para aplicar después',
        ])->assertRedirect();

        $note = AccountingCreditNote::first();
        $third = $this->issuedInvoice($agency, 25.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'apply_credit' => 25,
            'invoice_id' => $third->id,
            'amount' => 0,
        ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('accounting.credit-notes.void', $note), [
                'void_reason' => 'Se emitió por error',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $note->refresh();
        $this->assertSame('active', $note->status);
    }

    public function test_unused_overpayment_credit_is_reversed_when_payment_is_voided(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 50.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$invoice->id => 80],
        ])->assertRedirect();

        $payment = AccountingPayment::first();
        $this->actingAs($admin)
            ->post(route('accounting.payments.void', $payment), [
                'void_reason' => 'Cobro duplicado en caja',
            ])
            ->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $agency->refresh();
        $this->assertSame('issued', $invoice->status);
        $this->assertEquals(0.0, (float) $invoice->amount_paid);
        $this->assertEquals(0.0, (float) $agency->credit_balance_usd);
    }

    public function test_voiding_payment_restores_invoice_balance(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 100.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'allocations' => [$invoice->id => 100],
        ]);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);

        $payment = AccountingPayment::first();
        $this->actingAs($admin)->post(route('accounting.payments.void', $payment), [
            'void_reason' => 'Cobro duplicado por error',
        ])->assertRedirect(route('accounting.payments.index'));

        $payment->refresh();
        $invoice->refresh();
        $this->assertTrue($payment->isVoid());
        $this->assertSame('issued', $invoice->status);
        $this->assertEquals(100.0, $invoice->balanceUsd());
    }

    public function test_payments_index_lists_collections_with_account_and_invoice(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 174.0);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'agency_id' => $agency->id,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'deposit_account' => 'bank_bac',
            'allocations' => [$invoice->id => 174],
        ])->assertRedirect(route('accounting.payments.index'));

        $payment = AccountingPayment::first();
        $this->assertNotNull($payment);
        $this->assertSame('bank_bac', $payment->deposit_account);

        $this->actingAs($admin)
            ->get(route('accounting.payments.index'))
            ->assertOk()
            ->assertSee('Cobros')
            ->assertSee('Registrar cobro')
            ->assertSee($agency->name)
            ->assertSee('174.00')
            ->assertSee('Banco BAC')
            ->assertSee('#'.$invoice->id);

        $this->actingAs($admin)
            ->get(route('accounting.payments.show', $payment))
            ->assertOk()
            ->assertSee('Cobro #'.$payment->id)
            ->assertSee('1.1.02 Banco BAC')
            ->assertSee('Cancelar cobro');
    }

    public function test_register_payment_form_applies_single_invoice_amount(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 80.0);

        $this->actingAs($admin)
            ->get(route('accounting.payments.create'))
            ->assertOk()
            ->assertSee('Registrar cobro')
            ->assertSee('Monto recibido en caja')
            ->assertSee('Guardar cobro')
            ->assertSee('Saldo a favor del cliente')
            ->assertSee('#'.$invoice->id);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'invoice_id' => $invoice->id,
            'paid_at' => now()->toDateString(),
            'amount' => 25.5,
            'currency' => 'USD',
            'exchange_rate' => 36.5,
            'method' => 'transfer',
            'deposit_account' => 'bank_lafise',
            'reference' => 'REF-25',
            'commission' => 1.5,
        ])->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEquals(25.5, (float) $invoice->amount_paid);

        $payment = AccountingPayment::first();
        $this->assertSame('bank_lafise', $payment->deposit_account);
        $this->assertSame('REF-25', $payment->reference);
        $this->assertStringContainsString('Comisión: 1.50', (string) $payment->notes);
    }

    public function test_nio_payment_converts_with_rate_and_rejects_zero_rate(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 100.0);

        $this->actingAs($admin)
            ->from(route('accounting.payments.create'))
            ->post(route('accounting.payments.store'), [
                'invoice_id' => $invoice->id,
                'paid_at' => now()->toDateString(),
                'amount' => 3650,
                'currency' => 'NIO',
                'exchange_rate' => 0,
                'method' => 'cash',
            ])
            ->assertRedirect(route('accounting.payments.create'))
            ->assertSessionHasErrors('exchange_rate');

        $this->assertEquals(0.0, (float) $invoice->fresh()->amount_paid);

        $this->actingAs($admin)->post(route('accounting.payments.store'), [
            'invoice_id' => $invoice->id,
            'paid_at' => now()->toDateString(),
            'amount' => 3650,
            'currency' => 'NIO',
            'exchange_rate' => 36.5,
            'method' => 'cash',
        ])->assertRedirect(route('accounting.payments.index'));

        $invoice->refresh();
        $this->assertEquals(100.0, (float) $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_receivables_aging_shows_open_balances(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $invoice = $this->issuedInvoice($agency, 250.0);
        $invoice->update(['issued_at' => now()->subDays(45)]);

        $this->actingAs($admin)
            ->get(route('accounting.receivables.index'))
            ->assertOk()
            ->assertSee($agency->name)
            ->assertSee('250.00')
            ->assertSee('Saldo a favor');

        $this->actingAs($admin)
            ->get(route('accounting.receivables.show', $agency))
            ->assertOk()
            ->assertSee($invoice->folio)
            ->assertSee('Estado de cuenta')
            ->assertSee('Notas de crédito')
            ->assertSee('Movimientos de saldo a favor');
    }

    public function test_profitability_report_uses_rate_in_effect_on_delivery_date(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        AccountingRateCard::create([
            'agency_id' => $agency->id,
            'service_type' => 'AIR',
            'price_per_lb' => 5.00,
            'cost_per_lb' => 0,
            'currency' => 'USD',
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
        ]);
        AccountingOperatingCost::create([
            'service_type' => 'AIR',
            'cost_per_unit' => 3.00,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        $note = DeliveryNote::create(['code' => 'SLO-9601', 'agency_id' => $agency->id]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-PROFIT-1',
            'warehouse_code' => '960101',
            'label_name' => 'Cliente Rentable',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 10,
            'verified_weight_lbs' => 10,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subHours(2),
            'delivered_at' => now(),
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira Uno',
        ]);

        // 10 lbs * $5 = $50 ingreso; 10 * $3 = $30 costo; margen $20
        $this->actingAs($admin)
            ->get(route('accounting.profitability.index'))
            ->assertOk()
            ->assertSee($agency->name)
            ->assertSee('50.00')
            ->assertSee('30.00')
            ->assertSee('20.00');

        $this->actingAs($admin)
            ->get(route('accounting.profitability.show', $agency))
            ->assertOk()
            ->assertSee($agency->name)
            ->assertSee('960101')
            ->assertSee('50.00')
            ->assertSee('30.00')
            ->assertSee('20.00')
            ->assertSee('Paquetes del cliente en el período')
            ->assertSee('Histórico últimos 6 meses');
    }

    public function test_non_admin_cannot_access_accounting_submodules(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        $agency = $this->agency('A902');

        foreach ([
            route('accounting.rates.index'),
            route('accounting.rates.show', $agency),
            route('accounting.payments.index'),
            route('accounting.payments.create'),
            route('accounting.receivables.index'),
            route('accounting.credit-notes.index'),
            route('accounting.credit-notes.create'),
            route('accounting.profitability.index'),
            route('accounting.profitability.show', $agency),
            route('accounting.settings.edit'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect(route('packages.index'));
        }
    }
}
