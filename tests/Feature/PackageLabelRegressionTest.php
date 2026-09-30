<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageLabelRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_keeps_scan_fields_and_does_not_show_agency_codes(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->subagency('Holaex Label', 'H701');
        $package = $this->package($agency, [
            'warehouse_code' => '701111',
            'tracking_external' => 'TRK-LABEL-CORE-1',
            'label_name' => 'Michael Destinatario',
            'service_type' => 'AIR',
            'description' => 'Caja de ropa',
            'bulto_index' => 1,
            'bultos_total' => 2,
        ]);

        $html = $this->actingAs($user)
            ->get(route('preregistrations.label', $package))
            ->assertOk()
            ->assertSee('701111', false)
            ->assertSee('TRK-LABEL-CORE-1', false)
            ->assertSee('MICHAEL DESTINATARIO', false)
            ->assertSee('CAJA DE ROPA', false)
            ->assertSee('1 de 2', false)
            ->assertSee('AIR', false)
            ->assertSee('data-barcode="701111"', false)
            ->assertDontSee('H701 -', false)
            ->assertDontSee('qrserver.com', false)
            ->getContent();

        $this->assertStringContainsString('PrimeTrack Group', $html);
        $this->assertStringContainsString('Holaex Label', $html);
        $this->assertStringContainsString('sl-header-agency-frame', $html);
        $this->assertStringContainsString('barcode-'.$package->id.'-skylink', $html);
    }

    public function test_narrow_and_autoprint_label_still_open(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->package($this->subagency('Agencia Narrow', 'N802'), [
            'warehouse_code' => '802111',
            'service_type' => 'SEA',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.label', ['id' => $package->id, 'format' => 'narrow', 'autoprint' => 1]))
            ->assertOk()
            ->assertSee('2.25in 4in', false)
            ->assertSee('802111', false)
            ->assertSee('SEA', false)
            ->assertSee('printLabel', false);
    }

    public function test_dropoff_batch_prints_each_bulto_without_agency_codes(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->subagency('Dropoff Label', 'D903');
        $first = $this->package($agency, [
            'warehouse_code' => '903111',
            'tracking_external' => 'TRK-DROP-1',
            'intake_type' => 'DROP_OFF',
            'bulto_index' => 1,
            'bultos_total' => 2,
            'label_name' => 'Bulto Uno',
        ]);
        $second = $this->package($agency, [
            'warehouse_code' => '903111',
            'tracking_external' => '',
            'intake_type' => 'DROP_OFF',
            'bulto_index' => 2,
            'bultos_total' => 2,
            'label_name' => 'Bulto Dos',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.dropoff-labels', ['ids' => $first->id.','.$second->id]))
            ->assertOk()
            ->assertSee('903111', false)
            ->assertSee('TRK-DROP-1', false)
            ->assertSee('1 de 2', false)
            ->assertSee('2 de 2', false)
            ->assertSee('BULTO UNO', false)
            ->assertSee('BULTO DOS', false)
            ->assertDontSee('D903 -', false)
            ->assertSee('barcode-'.$first->id.'-skylink', false)
            ->assertSee('barcode-'.$second->id.'-skylink', false);
    }

    public function test_label_without_agency_still_prints_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->package(null, [
            'warehouse_code' => '555666',
            'tracking_external' => 'TRK-NO-AGENCY',
            'label_name' => 'Sin Agencia',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.label', $package))
            ->assertOk()
            ->assertSee('555666', false)
            ->assertSee('TRK-NO-AGENCY', false)
            ->assertSee('SIN AGENCIA', false)
            ->assertSee('PrimeTrack Group', false);
    }

    public function test_guest_cannot_open_the_label(): void
    {
        $package = $this->package($this->subagency('Guest Label', 'G110'), [
            'warehouse_code' => '110111',
        ]);

        $this->get(route('preregistrations.label', $package))
            ->assertRedirect(route('login'));
    }

    private function subagency(string $name, string $code): Agency
    {
        return Agency::create([
            'name' => $name,
            'code' => $code,
            'phone' => '2222-0000',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function package(?Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-LABEL-'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(200000, 299999),
            'label_name' => 'Destinatario',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency?->id,
        ], $overrides));
    }
}
