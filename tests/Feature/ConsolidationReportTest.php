<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_user_can_print_detailed_sack_report(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Reporte',
            'code' => 'R001',
            'phone' => '555-0101',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-REPORT-001',
            'warehouse_code' => '009999',
            'label_name' => 'Cliente de Prueba',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 10,
            'verified_weight_lbs' => 12.5,
            'dimension' => '18 x 13 x 24',
            'bulto_index' => 1,
            'bultos_total' => 2,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
        $consolidation = Consolidation::create([
            'code' => 'SAC-202607-9999',
            'service_type' => 'AIR',
            'transport_number' => '176-12345675',
            'status' => 'OPEN',
            'notes' => 'Manejar con cuidado',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($user)
            ->get(route('consolidations.report', $consolidation->id))
            ->assertOk()
            ->assertSee('REPORTE DE SACO')
            ->assertSee('Número de guía aérea')
            ->assertSee('176-12345675')
            ->assertSee('SAC-202607-9999')
            ->assertSee('8307 NW 68TH ST')
            ->assertSee('TRK-REPORT-001')
            ->assertSee('Cliente de Prueba')
            ->assertSee('Agencia Reporte')
            ->assertSee('12.50')
            ->assertSee('Cantidad de bultos')
            ->assertSee('Manejar con cuidado')
            ->assertDontSee('Kilos')
            ->assertDontSee('>Bulto<', false)
            ->assertSee('3.25')
            ->assertDontSee('Escaneado')
            ->assertDontSee('api.qrserver.com');
    }

    public function test_cubic_foot_package_goes_into_maritime_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia CFT Saco',
            'code' => 'R002',
            'phone' => '555-0102',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'warehouse_code' => '008888',
            'label_name' => 'Cliente Pie Cubico',
            'service_type' => 'CFT',
            'dimension' => '12 x 12 x 12',
            'intake_weight_lbs' => 8,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->post(route('consolidations.store'), [
                'service_type' => 'SEA',
                'transport_number' => 'msku1234567',
                'preregistration_ids' => [$package->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consolidations', [
            'service_type' => 'SEA',
            'transport_number' => 'MSKU1234567',
        ]);
        $sea = Consolidation::where('service_type', 'SEA')->where('transport_number', 'MSKU1234567')->first();
        $this->assertNotNull($sea);
        $this->assertStringStartsWith('CNT-', $sea->code);
        $this->assertDatabaseHas('consolidation_items', [
            'preregistration_id' => $package->id,
        ]);
    }

    public function test_air_consolidation_requires_awb_and_uses_sack_prefix(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia AWB',
            'code' => 'R003',
            'phone' => '555-0103',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-AIR-AWB',
            'warehouse_code' => '007777',
            'label_name' => 'Cliente Aereo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->from(route('consolidations.create-select'))
            ->post(route('consolidations.store'), [
                'service_type' => 'AIR',
                'preregistration_ids' => [$package->id],
            ])
            ->assertRedirect(route('consolidations.create-select'))
            ->assertSessionHasErrors('transport_number');

        $this->actingAs($user)
            ->post(route('consolidations.store'), [
                'service_type' => 'AIR',
                'transport_number' => '176-98765432',
                'preregistration_ids' => [$package->id],
            ])
            ->assertRedirect();

        $air = Consolidation::where('service_type', 'AIR')->where('transport_number', '176-98765432')->first();
        $this->assertNotNull($air);
        $this->assertStringStartsWith('SAC-', $air->code);
        $this->assertSame('saco', $air->unitNoun());
        $this->assertSame('Número de guía aérea', $air->transportNumberLabel());
    }

    public function test_sea_consolidation_requires_container_number_and_uses_container_prefix(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia CNT',
            'code' => 'R004',
            'phone' => '555-0104',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-SEA-CNT',
            'warehouse_code' => '006666',
            'label_name' => 'Cliente Maritimo',
            'service_type' => 'SEA',
            'intake_weight_lbs' => 40,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->from(route('consolidations.create-select'))
            ->post(route('consolidations.store'), [
                'service_type' => 'SEA',
                'preregistration_ids' => [$package->id],
            ])
            ->assertRedirect(route('consolidations.create-select'))
            ->assertSessionHasErrors('transport_number');

        $this->actingAs($user)
            ->post(route('consolidations.store'), [
                'service_type' => 'SEA',
                'transport_number' => 'TEMU8899001',
                'preregistration_ids' => [$package->id],
            ])
            ->assertRedirect();

        $sea = Consolidation::where('service_type', 'SEA')->where('transport_number', 'TEMU8899001')->first();
        $this->assertNotNull($sea);
        $this->assertStringStartsWith('CNT-', $sea->code);
        $this->assertSame('contenedor', $sea->unitNoun());
        $this->assertSame('Número de contenedor', $sea->transportNumberLabel());

        $this->actingAs($user)
            ->get(route('consolidations.report', $sea->id))
            ->assertOk()
            ->assertSee('REPORTE DE CONTENEDOR')
            ->assertSee('Número de contenedor')
            ->assertSee('TEMU8899001')
            ->assertDontSee('REPORTE DE SACO');
    }

    public function test_unitary_air_package_requires_awb(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Unitario',
            'code' => 'R005',
            'phone' => '555-0105',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-UNIT-AIR',
            'warehouse_code' => '005555',
            'label_name' => 'Solo caja',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->post(route('preregistrations.create-single-consolidation', $package->id))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHasErrors('transport_number');

        $this->actingAs($user)
            ->post(route('preregistrations.create-single-consolidation', $package->id), [
                'transport_number' => '176-11112222',
            ])
            ->assertRedirect();

        $air = Consolidation::where('transport_number', '176-11112222')->first();
        $this->assertNotNull($air);
        $this->assertSame('AIR', $air->service_type);
        $this->assertStringStartsWith('SAC-', $air->code);
    }
}
