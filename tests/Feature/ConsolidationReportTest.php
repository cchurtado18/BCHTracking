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
}
