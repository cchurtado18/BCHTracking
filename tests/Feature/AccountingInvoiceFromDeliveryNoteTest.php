<?php

namespace Tests\Feature;

use App\Mail\InvoiceSentToClient;
use App\Models\AccountingInvoice;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountingInvoiceFromDeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    private function seedNoteWithPackages(): array
    {
        $agency = Agency::create([
            'name' => 'Agencia Factura Test',
            'code' => 'F901',
            'phone' => '2222-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);

        $note = DeliveryNote::create([
            'code' => 'SLO-9501',
            'agency_id' => $agency->id,
        ]);

        $air = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-AIR-1',
            'warehouse_code' => '950101',
            'label_name' => 'Cliente A',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 10,
            'verified_weight_lbs' => 12,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);

        $sea = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-SEA-1',
            'warehouse_code' => '950102',
            'label_name' => 'Cliente B',
            'service_type' => 'SEA',
            'intake_weight_lbs' => 20,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);

        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $air->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira Uno',
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $sea->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira Uno',
        ]);

        return compact('agency', 'note', 'air', 'sea');
    }

    public function test_admin_can_create_invoice_from_delivery_note_and_print_voucher(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)
            ->get(route('accounting.invoices.create-from-note', $note))
            ->assertOk()
            ->assertSee('Generar factura PrimeTrack')
            ->assertSee('SLO-9501');

        $response = $this->actingAs($admin)
            ->post(route('accounting.invoices.store-from-note', $note), [
                'rate_air' => 3.5,
                'rate_sea' => 1.25,
                'exchange_rate' => 36.5,
                'persist_rates' => 1,
            ]);

        $invoice = AccountingInvoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame($note->id, $invoice->delivery_note_id);
        $this->assertStringStartsWith('FP-', $invoice->folio);
        // 12 lbs AIR * 3.5 + 20 lbs SEA * 1.25 = 42 + 25 = 67
        $this->assertEquals(67.0, (float) $invoice->total_usd);
        $this->assertEquals(12 + 20, (float) $invoice->total_lbs);
        $this->assertEquals(2, $invoice->lines()->count());

        $response->assertRedirect(route('accounting.invoices.voucher', $invoice));

        $this->actingAs($admin)
            ->get(route('accounting.invoices.voucher', $invoice))
            ->assertOk()
            ->assertSee('NOTA DE COBRO SKYLINK ONE')
            ->assertSee($invoice->folio)
            ->assertSee('Flete Aereo')
            ->assertSee('Flete Maritimo')
            ->assertSee('SLO-9501');

        $pdf = $this->actingAs($admin)
            ->get(route('accounting.invoices.pdf', $invoice));

        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $this->actingAs($admin)
            ->get(route('accounting.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('NOTA DE COBRO SKYLINK ONE')
            ->assertSee('Líneas por servicio')
            ->assertSee('Cliente y emisión')
            ->assertSee($invoice->folio);
    }

    public function test_invoice_bills_cubic_feet_for_cft_service(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = Agency::create([
            'name' => 'Agencia CFT Test',
            'code' => 'F902',
            'phone' => '2222-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-9502',
            'agency_id' => $agency->id,
        ]);
        $pkg = Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'warehouse_code' => '950201',
            'label_name' => 'Cliente CFT',
            'service_type' => 'CFT',
            'dimension' => '12 x 12 x 12',
            'intake_weight_lbs' => 8,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $pkg->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira CFT',
        ]);

        $this->actingAs($admin)
            ->post(route('accounting.invoices.store-from-note', $note), [
                'rate_cft' => 10,
                'exchange_rate' => 36.5,
                'persist_rates' => 1,
            ])
            ->assertRedirect();

        $invoice = AccountingInvoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1.0, (float) $invoice->lines()->first()->quantity_lbs);
        $this->assertEquals(10.0, (float) $invoice->total_usd);
        $this->assertEquals(0.0, (float) $invoice->total_lbs);
        $this->assertSame('CFT', $invoice->lines()->first()->service_type);
        $this->assertSame('Flete Pie Cubico', $invoice->lines()->first()->description);

        $this->actingAs($admin)
            ->get(route('accounting.invoices.voucher', $invoice))
            ->assertOk()
            ->assertSee('Flete Pie Cubico')
            ->assertSee('TOTAL PIE');
    }

    public function test_cannot_create_second_active_invoice_for_same_note(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 1,
            'rate_sea' => 1,
            'exchange_rate' => 36,
        ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('accounting.invoices.store-from-note', $note), [
                'rate_air' => 1,
                'rate_sea' => 1,
                'exchange_rate' => 36,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, AccountingInvoice::count());
    }

    public function test_non_admin_cannot_access_accounting_invoices(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($user)
            ->get(route('accounting.invoices.index'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($user)
            ->get(route('accounting.invoices.create-from-note', $note))
            ->assertRedirect(route('packages.index'));
    }

    public function test_admin_can_start_create_from_invoices_module(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)
            ->get(route('accounting.invoices.create'))
            ->assertOk()
            ->assertSee('Nueva factura PrimeTrack')
            ->assertSee('SLO-9501');

        $this->actingAs($admin)
            ->post(route('accounting.invoices.start-create'), [
                'delivery_note_id' => $note->id,
            ])
            ->assertRedirect(route('accounting.invoices.create-from-note', $note));
    }

    public function test_admin_can_void_invoice_and_issue_another_for_same_note(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 1,
            'rate_sea' => 1,
            'exchange_rate' => 36,
        ])->assertRedirect();

        $first = AccountingInvoice::first();
        $this->actingAs($admin)
            ->post(route('accounting.invoices.void', $first), [
                'void_reason' => 'Tarifa incorrecta',
            ])
            ->assertRedirect(route('accounting.invoices.show', $first));

        $first->refresh();
        $this->assertTrue($first->isVoid());
        $this->assertSame('Tarifa incorrecta', $first->void_reason);

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 2,
            'rate_sea' => 2,
            'exchange_rate' => 36,
        ])->assertRedirect();

        $this->assertSame(2, AccountingInvoice::count());
        $this->assertSame(1, AccountingInvoice::query()->where('status', '!=', 'void')->count());
    }

    public function test_credit_is_applied_when_issuing_invoice_from_delivery_note(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['agency' => $agency, 'note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.credit-notes.store'), [
            'agency_id' => $agency->id,
            'amount_usd' => 100,
            'reason' => 'Saldo a favor por aplicar al emitir',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->get(route('accounting.invoices.create-from-note', $note))
            ->assertOk()
            ->assertSee('Aplicar saldo a favor')
            ->assertSee('100.00');

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 3.5,
            'rate_sea' => 1.25,
            'exchange_rate' => 36.5,
            'apply_credit' => 1,
            'apply_credit_amount' => 100,
        ])->assertRedirect();

        $invoice = AccountingInvoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(67.0, (float) $invoice->total_usd);
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(67.0, (float) $invoice->amount_paid);
        $this->assertEquals(33.0, (float) $agency->fresh()->credit_balance_usd);

        $this->actingAs($admin)
            ->post(route('accounting.invoices.void', $invoice), [
                'void_reason' => 'Se facturó con tarifa incorrecta',
            ])
            ->assertRedirect(route('accounting.invoices.show', $invoice));

        $this->assertEquals(100.0, (float) $agency->fresh()->credit_balance_usd);
    }

    public function test_cannot_delete_issued_invoice_until_voided(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 1,
            'rate_sea' => 1,
            'exchange_rate' => 36,
        ]);

        $invoice = AccountingInvoice::first();
        $this->actingAs($admin)
            ->delete(route('accounting.invoices.destroy', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('accounting_invoices', ['id' => $invoice->id]);

        $this->actingAs($admin)->post(route('accounting.invoices.void', $invoice), [
            'void_reason' => 'Se emitió por error',
        ]);

        $this->actingAs($admin)
            ->delete(route('accounting.invoices.destroy', $invoice))
            ->assertRedirect(route('accounting.invoices.index'));

        $this->assertDatabaseMissing('accounting_invoices', ['id' => $invoice->id]);
    }

    public function test_admin_can_email_invoice_to_client_billing_address(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['agency' => $agency, 'note' => $note] = $this->seedNoteWithPackages();
        $agency->update(['billing_email' => 'facturas@cliente.test']);

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 3.5,
            'rate_sea' => 1.25,
            'exchange_rate' => 36.5,
        ]);

        $invoice = AccountingInvoice::first();
        $this->assertNotNull($invoice);

        $this->actingAs($admin)
            ->from(route('accounting.invoices.index'))
            ->post(route('accounting.invoices.send', $invoice))
            ->assertRedirect(route('accounting.invoices.index'))
            ->assertSessionHas('success');

        Mail::assertSent(InvoiceSentToClient::class, function (InvoiceSentToClient $mail) use ($invoice) {
            return $mail->invoice->is($invoice)
                && $mail->hasTo('facturas@cliente.test');
        });

        $this->assertNotNull($invoice->fresh()->emailed_at);
    }

    public function test_cannot_email_invoice_without_client_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 1,
            'rate_sea' => 1,
            'exchange_rate' => 36,
        ]);

        $invoice = AccountingInvoice::first();

        $this->actingAs($admin)
            ->from(route('accounting.invoices.index'))
            ->post(route('accounting.invoices.send', $invoice))
            ->assertRedirect(route('accounting.invoices.index'))
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_invoice_index_hides_voided_unless_requested(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 1,
            'rate_sea' => 1,
            'exchange_rate' => 36,
        ]);

        $invoice = AccountingInvoice::first();
        $this->actingAs($admin)
            ->post(route('accounting.invoices.void', $invoice), [
                'void_reason' => 'Prueba de filtro de anuladas',
            ])
            ->assertRedirect(route('accounting.invoices.show', $invoice));

        $this->assertTrue($invoice->fresh()->isVoid());

        $this->actingAs($admin)
            ->get(route('accounting.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('NOTA DE COBRO SKYLINK ONE')
            ->assertSee('Líneas por servicio')
            ->assertSee('ANULADA');

        $this->actingAs($admin)
            ->get(route('accounting.invoices.index'))
            ->assertOk()
            ->assertSee('Aún no hay facturas')
            ->assertDontSee('>'.$invoice->folio.'<', false);

        $this->actingAs($admin)
            ->get(route('accounting.invoices.index', ['include_void' => 1]))
            ->assertOk()
            ->assertSee($invoice->folio);
    }

    public function test_agency_user_can_view_own_invoices_but_not_create_or_see_others(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['agency' => $agency, 'note' => $note] = $this->seedNoteWithPackages();
        $client = User::factory()->create(['agency_id' => $agency->id, 'is_admin' => false]);

        $this->actingAs($admin)
            ->post(route('accounting.invoices.store-from-note', $note), [
                'rate_air' => 3.5,
                'rate_sea' => 1.25,
                'exchange_rate' => 36.5,
            ]);

        $invoice = AccountingInvoice::query()->where('agency_id', $agency->id)->first();
        $this->assertNotNull($invoice);

        $otherAgency = Agency::create([
            'name' => 'Otra Agencia Factura',
            'code' => 'X999',
            'phone' => '1111-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);
        $otherInvoice = AccountingInvoice::create([
            'folio' => 'FP-OTHER-1',
            'agency_id' => $otherAgency->id,
            'status' => 'issued',
            'issued_at' => now()->toDateString(),
            'total_lbs' => 1,
            'total_usd' => 10,
            'total_cor' => 365,
            'exchange_rate' => 36.5,
        ]);

        $this->actingAs($client)
            ->get(route('accounting.invoices.index'))
            ->assertOk()
            ->assertSee('Mis facturas')
            ->assertSee($invoice->folio)
            ->assertDontSee($otherInvoice->folio)
            ->assertDontSee('Nueva factura');

        $this->actingAs($client)
            ->get(route('accounting.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->folio)
            ->assertDontSee('Registrar cobro')
            ->assertDontSee('Anular factura')
            ->assertDontSee('Enviar al cliente');

        $this->actingAs($client)
            ->get(route('accounting.invoices.voucher', $invoice))
            ->assertOk()
            ->assertSee($invoice->folio);

        $this->actingAs($client)
            ->get(route('accounting.invoices.show', $otherInvoice))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('accounting.invoices.create'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($client)
            ->post(route('accounting.invoices.send', $invoice))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($client)
            ->get(route('api-tokens.index'))
            ->assertRedirect(route('packages.index'));
    }

    public function test_cannot_issue_zero_dollar_invoice_without_rates(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)
            ->from(route('accounting.invoices.create-from-note', $note))
            ->post(route('accounting.invoices.store-from-note', $note), [
                'exchange_rate' => 36.5,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, AccountingInvoice::count());
    }

    public function test_public_voucher_is_gone_after_void(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['agency' => $agency, 'note' => $note] = $this->seedNoteWithPackages();
        $agency->update(['billing_email' => 'cobro@cliente.test']);

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 3.5,
            'rate_sea' => 1.25,
            'exchange_rate' => 36.5,
        ]);

        $invoice = AccountingInvoice::first();
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'accounting.invoices.public-voucher',
            now()->addDay(),
            ['invoice' => $invoice->id]
        );

        $this->actingAs($admin)
            ->get(route('accounting.invoices.voucher', $invoice))
            ->assertOk()
            ->assertSee('Descargar PDF')
            ->assertSee('Volver');

        auth()->logout();
        $this->flushSession();

        $this->get($url)
            ->assertOk()
            ->assertSee('cobro@cliente.test')
            ->assertSee('Imprimir voucher')
            ->assertDontSee('Descargar PDF')
            ->assertDontSee('>Volver<', false);

        $this->actingAs($admin)->post(route('accounting.invoices.void', $invoice), [
            'void_reason' => 'Factura emitida por error',
        ]);

        $this->get($url)->assertStatus(410);
    }

    public function test_cannot_remove_package_from_invoiced_delivery_note(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note] = $this->seedNoteWithPackages();

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 3.5,
            'rate_sea' => 1.25,
            'exchange_rate' => 36.5,
        ]);

        $delivery = Delivery::query()->where('delivery_note_id', $note->id)->first();
        $this->assertNotNull($delivery);

        $this->actingAs($admin)
            ->from(route('salidas.hojas.edit', $note))
            ->delete(route('salidas.hojas.remove-package', [$note, $delivery]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id]);
    }

    public function test_cannot_delete_client_with_invoice_history(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = Agency::create([
            'name' => 'Cliente con factura',
            'code' => 'Z100',
            'phone' => '2222-1111',
            'is_active' => true,
            'is_main' => false,
        ]);
        AccountingInvoice::create([
            'folio' => 'FP-KEEP-1',
            'agency_id' => $agency->id,
            'status' => 'issued',
            'issued_at' => now()->toDateString(),
            'total_lbs' => 1,
            'total_usd' => 10,
            'total_cor' => 365,
            'exchange_rate' => 36.5,
        ]);

        $this->actingAs($admin)
            ->delete(route('agencies.destroy', $agency))
            ->assertRedirect(route('agencies.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('agencies', ['id' => $agency->id]);
    }

    public function test_agency_user_cannot_list_voided_invoices_via_query(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['agency' => $agency, 'note' => $note] = $this->seedNoteWithPackages();
        $client = User::factory()->create(['agency_id' => $agency->id, 'is_admin' => false]);

        $this->actingAs($admin)->post(route('accounting.invoices.store-from-note', $note), [
            'rate_air' => 3.5,
            'rate_sea' => 1.25,
            'exchange_rate' => 36.5,
        ]);
        $invoice = AccountingInvoice::first();
        $this->actingAs($admin)->post(route('accounting.invoices.void', $invoice), [
            'void_reason' => 'Anulada para ocultar al cliente',
        ]);

        $this->actingAs($client);
        $this->assertTrue($client->isAgencyUser());
        $this->assertSame('void', $invoice->fresh()->status);

        $this->actingAs($client)
            ->get(route('accounting.invoices.index', ['status' => 'void', 'include_void' => 1]))
            ->assertOk()
            ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 0);
    }
}
