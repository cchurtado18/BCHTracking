<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreregistrationConsolidationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_consolidation_filter_options(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertSee('name="consolidation"', false)
            ->assertSee('No agregados')
            ->assertSee('Ya agregados');
    }

    public function test_pending_filter_hides_packages_already_in_a_sack_or_container(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $looseAir = $this->package($agency, 'AIR', 'TRK-LOOSE-AIR', '881001');
        $looseSea = $this->package($agency, 'SEA', 'TRK-LOOSE-SEA', '881002');
        $inSack = $this->package($agency, 'AIR', 'TRK-IN-SACK', '881003');
        $inContainer = $this->package($agency, 'SEA', 'TRK-IN-CONT', '881004');

        $this->putInUnit($inSack, 'AIR', 'SAC-FLT-1');
        $this->putInUnit($inContainer, 'SEA', 'CTR-FLT-1');

        $this->actingAs($user)
            ->get(route('preregistrations.index', ['consolidation' => 'pending']))
            ->assertOk()
            ->assertSee('881001')
            ->assertSee('881002')
            ->assertDontSee('881003')
            ->assertDontSee('881004');
    }

    public function test_assigned_filter_shows_only_packages_in_a_sack_or_container(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $this->package($agency, 'AIR', 'TRK-STILL-LOOSE', '882001');
        $inSack = $this->package($agency, 'AIR', 'TRK-ALREADY-SACK', '882002');
        $this->putInUnit($inSack, 'AIR', 'SAC-FLT-2');

        $this->actingAs($user)
            ->get(route('preregistrations.index', ['consolidation' => 'assigned']))
            ->assertOk()
            ->assertSee('882002')
            ->assertDontSee('882001');
    }

    public function test_air_plus_pending_is_packages_not_in_a_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $this->package($agency, 'AIR', 'TRK-AIR-LOOSE', '883001');
        $inSack = $this->package($agency, 'AIR', 'TRK-AIR-SACKED', '883002');
        $this->package($agency, 'SEA', 'TRK-SEA-LOOSE', '883003');
        $this->putInUnit($inSack, 'AIR', 'SAC-FLT-3');

        $this->actingAs($user)
            ->get(route('preregistrations.index', [
                'service_type' => 'AIR',
                'consolidation' => 'pending',
            ]))
            ->assertOk()
            ->assertSee('883001')
            ->assertDontSee('883002')
            ->assertDontSee('883003');
    }

    public function test_pending_filter_ignores_delivered_and_later_statuses_without_a_unit(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $this->package($agency, 'AIR', 'TRK-STILL-MIAMI', '884001');
        $this->package($agency, 'AIR', 'TRK-PHOTO-OPEN', '884002', 'PHOTO_PENDING');
        $this->package($agency, 'AIR', 'TRK-ALREADY-DELIVERED', '884003', 'DELIVERED');
        $this->package($agency, 'AIR', 'TRK-ALREADY-READY', '884004', 'READY');
        $this->package($agency, 'AIR', 'TRK-ALREADY-TRANSIT', '884005', 'IN_TRANSIT');

        $this->actingAs($user)
            ->get(route('preregistrations.index', [
                'service_type' => 'AIR',
                'consolidation' => 'pending',
            ]))
            ->assertOk()
            ->assertSee('884001')
            ->assertSee('884002')
            ->assertDontSee('884003')
            ->assertDontSee('884004')
            ->assertDontSee('884005');
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Filtro Consolidacion',
            'code' => 'FLT'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function package(Agency $agency, string $service, string $tracking, string $warehouseCode, string $status = 'RECEIVED_MIAMI'): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => $tracking,
            'warehouse_code' => $warehouseCode,
            'label_name' => '[PENDIENTE]',
            'service_type' => $service,
            'intake_weight_lbs' => 2,
            'status' => $status,
            'agency_id' => $agency->id,
        ]);
    }

    private function putInUnit(Preregistration $package, string $service, string $code): void
    {
        $unit = Consolidation::create([
            'code' => $code,
            'service_type' => $service,
            'status' => 'OPEN',
        ]);

        ConsolidationItem::create([
            'consolidation_id' => $unit->id,
            'preregistration_id' => $package->id,
            'scanned_at' => now(),
        ]);
    }
}
