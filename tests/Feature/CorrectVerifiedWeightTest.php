<?php

namespace Tests\Feature;

use App\Models\AccountingInvoice;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectVerifiedWeightTest extends TestCase
{
    use RefreshDatabase;

    private function createAgency(): Agency
    {
        $suffix = (string) random_int(1000, 9999);

        return Agency::create([
            'name' => 'Agencia Peso '.$suffix,
            'code' => 'W'.$suffix,
            'phone' => '555-0200',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{agency: Agency, note: DeliveryNote, package: Preregistration}
     */
    private function seedDeliveredPackage(array $overrides = []): array
    {
        $agency = $this->createAgency();
        $package = Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-WGT-'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(100000, 999999),
            'label_name' => 'Cliente Peso',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 10,
            'verified_weight_lbs' => 12,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subDay(),
            'delivered_at' => now(),
        ], $overrides));
        $note = DeliveryNote::create([
            'code' => 'SLO-W'.random_int(1000, 9999),
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira Peso',
            'delivery_type' => 'PICKUP',
        ]);

        return compact('agency', 'note', 'package');
    }

    public function test_package_show_works_before_and_after_delivery(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = $this->createAgency();
        $pending = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-WGT-SHOW',
            'warehouse_code' => '112233',
            'label_name' => 'Pendiente Show',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($admin)
            ->get(route('packages.show', $pending->id))
            ->assertOk()
            ->assertDontSee('Editar peso');

        ['package' => $package] = $this->seedDeliveredPackage();

        $this->actingAs($admin)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('Editar peso');
    }

    public function test_central_user_can_correct_weight_after_delivery_and_note_uses_it(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note, 'package' => $package] = $this->seedDeliveredPackage();

        $this->actingAs($admin)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('Editar peso')
            ->assertSee('name="verified_weight_lbs"', false);

        $this->actingAs($admin)
            ->from(route('packages.show', $package->id))
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 18.5,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('success');

        $this->assertEquals(18.5, (float) $package->fresh()->verified_weight_lbs);
        $this->assertTrue(
            AuditLog::query()
                ->where('auditable_type', 'preregistration')
                ->where('auditable_id', $package->id)
                ->where('summary', 'like', '%Peso verificado:%')
                ->exists()
        );

        $this->actingAs($admin)
            ->get(route('salidas.print-report', ['delivery_note_id' => $note->id]))
            ->assertOk()
            ->assertSee('18.50')
            ->assertDontSee('12.00');

        $this->actingAs($admin)
            ->get(route('salidas.hojas.edit', $note))
            ->assertOk()
            ->assertSee('18.5')
            ->assertDontSee('Corregir peso');
    }

    public function test_corrected_weight_is_used_when_invoicing_the_note(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note, 'package' => $package] = $this->seedDeliveredPackage();

        $this->actingAs($admin)
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 18.5,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('accounting.invoices.store-from-note', $note), [
                'rate_air' => 2,
                'rate_sea' => 1,
                'exchange_rate' => 36.5,
            ])
            ->assertRedirect();

        $invoice = AccountingInvoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(18.5, (float) $invoice->total_lbs);
        $this->assertEquals(37.0, (float) $invoice->total_usd);
    }

    public function test_active_invoice_blocks_weight_correction(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note, 'package' => $package] = $this->seedDeliveredPackage();
        AccountingInvoice::create([
            'folio' => 'FP-WGT-LOCK',
            'delivery_note_id' => $note->id,
            'agency_id' => $note->agency_id,
            'status' => 'issued',
            'issued_at' => now()->toDateString(),
            'total_lbs' => 12,
            'total_usd' => 24,
            'total_cor' => 876,
            'exchange_rate' => 36.5,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('FP-WGT-LOCK')
            ->assertSee('Anúlela')
            ->assertDontSee('Editar peso');

        $this->actingAs($admin)
            ->from(route('packages.show', $package->id))
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 22,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('error');

        $this->assertEquals(12.0, (float) $package->fresh()->verified_weight_lbs);
    }

    public function test_voided_invoice_allows_weight_correction(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['note' => $note, 'package' => $package] = $this->seedDeliveredPackage();
        AccountingInvoice::create([
            'folio' => 'FP-WGT-VOID',
            'delivery_note_id' => $note->id,
            'agency_id' => $note->agency_id,
            'status' => 'void',
            'issued_at' => now()->toDateString(),
            'total_lbs' => 12,
            'total_usd' => 24,
            'total_cor' => 876,
            'exchange_rate' => 36.5,
            'amount_paid' => 0,
            'void_reason' => 'Peso incorrecto',
            'voided_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 16,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('success');

        $this->assertEquals(16.0, (float) $package->fresh()->verified_weight_lbs);
    }

    public function test_agency_user_cannot_correct_verified_weight(): void
    {
        ['agency' => $agency, 'package' => $package] = $this->seedDeliveredPackage();
        $client = User::factory()->create(['agency_id' => $agency->id, 'is_admin' => false]);

        $this->actingAs($client)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertDontSee('Editar peso');

        $this->actingAs($client)
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 40,
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertEquals(12.0, (float) $package->fresh()->verified_weight_lbs);
    }

    public function test_cannot_correct_weight_before_package_is_ready(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = $this->createAgency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-WGT-NIC',
            'warehouse_code' => '334455',
            'label_name' => 'Aún en NIC',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 9,
            'status' => 'IN_WAREHOUSE_NIC',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($admin)
            ->from(route('packages.show', $package->id))
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 11,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('error');

        $this->assertNull($package->fresh()->verified_weight_lbs);
    }

    public function test_ready_package_without_note_can_still_correct_weight(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = $this->createAgency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-WGT-READY',
            'warehouse_code' => '556677',
            'label_name' => 'Listo Peso',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 7,
            'verified_weight_lbs' => 8,
            'status' => 'READY',
            'agency_id' => $agency->id,
            'ready_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 9.25,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('success');

        $this->assertEquals(9.25, (float) $package->fresh()->verified_weight_lbs);
    }

    public function test_operational_user_with_packages_module_can_correct_weight(): void
    {
        $ops = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => [Permission::MODULE_PACKAGES],
        ]);
        ['package' => $package] = $this->seedDeliveredPackage();

        $this->actingAs($ops)
            ->post(route('packages.verified-weight', $package->id), [
                'verified_weight_lbs' => 14,
            ])
            ->assertRedirect(route('packages.show', $package->id))
            ->assertSessionHas('success');

        $this->assertEquals(14.0, (float) $package->fresh()->verified_weight_lbs);
    }
}
