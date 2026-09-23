<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseCodeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_photo_pending_assigns_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->packageWithoutCode($agency, [
            'label_name' => '[PENDIENTE]',
            'status' => 'PHOTO_PENDING',
            'agency_id' => null,
        ]);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'AIMAN EL CHARRANI',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 5.7,
                'tracking_external' => $package->tracking_external,
            ])
            ->assertRedirect();

        $package->refresh();
        $this->assertSame('RECEIVED_MIAMI', $package->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $package->warehouse_code);
    }

    public function test_packages_index_assigns_missing_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->packageWithoutCode($agency, [
            'tracking_external' => 'TBTA33437679360',
            'label_name' => 'AIMAN EL CHARRANI',
            'status' => 'IN_TRANSIT',
        ]);

        $response = $this->actingAs($user)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('TBTA33437679360')
            ->assertSee('AIMAN EL CHARRANI');

        $package->refresh();
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $package->warehouse_code);
        $response->assertSee($package->warehouse_code);
    }

    public function test_package_show_assigns_missing_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->packageWithoutCode($this->agency(), [
            'status' => 'IN_TRANSIT',
        ]);

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk();

        $package->refresh();
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $package->warehouse_code);
    }

    public function test_sending_sack_assigns_missing_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->packageWithoutCode($this->agency(), [
            'status' => 'RECEIVED_MIAMI',
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-202609-0001',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($user)
            ->from(route('consolidations.show', $sack->id))
            ->post(route('consolidations.send', $sack->id))
            ->assertRedirect();

        $package->refresh();
        $this->assertSame('IN_TRANSIT', $package->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $package->warehouse_code);
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'MEI CARGO',
            'code' => '0010',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function packageWithoutCode(Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TBTA'.random_int(100000000, 999999999),
            'label_name' => 'DESTINATARIO',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5.7,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ], $overrides));
    }
}
