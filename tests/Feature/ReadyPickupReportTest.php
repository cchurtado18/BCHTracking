<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadyPickupReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_downloads_ready_pickup_pdf_for_a_client(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->readyPackage($agency, [
            'warehouse_code' => '445501',
            'tracking_external' => 'TRK-READY-PDF-1',
            'label_name' => 'Maria Destinatario',
            'verified_weight_lbs' => 4.5,
        ]);

        $response = $this->actingAs($user)
            ->get(route('salidas.ready-report', ['agency_id' => $agency->id]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'listos-retiro-'.$agency->code,
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($package->warehouse_code, $this->readyReportHtml($agency));
    }

    public function test_delivered_package_is_not_included_in_ready_pickup_pdf(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $ready = $this->readyPackage($agency, [
            'warehouse_code' => '445502',
            'tracking_external' => 'TRK-READY-KEEP',
        ]);
        $delivered = $this->readyPackage($agency, [
            'warehouse_code' => '445503',
            'tracking_external' => 'TRK-READY-GONE',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-9801',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'preregistration_id' => $delivered->id,
            'delivery_note_id' => $note->id,
            'delivered_to' => 'Receptor',
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('salidas.ready-report', ['agency_id' => $agency->id]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $html = $this->readyReportHtml($agency);
        $this->assertStringContainsString($ready->warehouse_code, $html);
        $this->assertStringNotContainsString($delivered->warehouse_code, $html);
    }

    public function test_account_without_ready_packages_does_not_download_empty_pdf(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();

        $this->actingAs($user)
            ->get(route('salidas.ready-report', ['agency_id' => $agency->id]))
            ->assertRedirect(route('salidas.create', ['agency_id' => $agency->id]))
            ->assertSessionHas('error', 'Esta cuenta no tiene paquetes listos para retiro.');
    }

    public function test_guest_cannot_download_ready_pickup_pdf(): void
    {
        $agency = $this->agency();

        $this->get(route('salidas.ready-report', ['agency_id' => $agency->id]))
            ->assertRedirect(route('login'));
    }

    public function test_create_sheet_and_client_show_offer_the_pdf(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $agency = $this->agency();
        $this->readyPackage($agency, ['warehouse_code' => '445504']);

        $this->actingAs($user)
            ->get(route('salidas.create', ['agency_id' => $agency->id]))
            ->assertOk()
            ->assertSee(route('salidas.ready-report', ['agency_id' => $agency->id]), false)
            ->assertSee('Descargar PDF', false);

        $this->actingAs($user)
            ->get(route('agencies.show', $agency->id))
            ->assertOk()
            ->assertSee('Listos para retiro (PDF)', false)
            ->assertSee(route('salidas.ready-report', ['agency_id' => $agency->id]), false);
    }

    private function readyReportHtml(Agency $agency): string
    {
        $packages = Preregistration::query()
            ->where('status', 'READY')
            ->whereDoesntHave('delivery')
            ->whereIn('agency_id', $agency->deliveryNetworkIds())
            ->orderBy('warehouse_code')
            ->get();

        return view('deliveries.ready-report-pdf', [
            'agency' => $agency,
            'accountName' => $agency->listingAccountLabel(),
            'consignee' => '',
            'serviceType' => null,
            'packages' => $packages,
            'totals' => [
                'count' => $packages->count(),
                'lbs' => $packages->sum(fn (Preregistration $p) => (float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0)),
                'air_count' => 0,
                'air_lbs' => 0,
                'sea_count' => 0,
                'sea_lbs' => 0,
                'cft_count' => 0,
                'cft_lbs' => 0,
            ],
            'printedAt' => now(),
        ])->render();
    }

    private function agency(): Agency
    {
        $suffix = (string) random_int(1000, 9999);

        return Agency::create([
            'name' => 'Cliente Listos '.$suffix,
            'code' => 'R'.$suffix,
            'phone' => '555-0200',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function readyPackage(Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-READY-'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(400000, 499999),
            'label_name' => 'Destinatario Listo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'verified_weight_lbs' => 3,
            'status' => 'READY',
            'agency_id' => $agency->id,
            'ready_at' => now(),
        ], $overrides));
    }
}
