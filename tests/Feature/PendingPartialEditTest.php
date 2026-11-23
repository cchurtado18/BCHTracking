<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use App\Support\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingPartialEditTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZPARTIAL0001',
            'warehouse_code' => '008001',
            'label_name' => '[PENDIENTE]',
            'service_type' => 'AIR',
            'status' => 'PHOTO_PENDING',
        ], $overrides));
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Parcial',
            'code' => 'PA01',
            'phone' => '555-0123',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    public function test_pending_package_can_switch_to_cubic_feet_without_agency_and_stays_pending(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending();

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => '',
                'label_name' => '[PENDIENTE]',
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '20 x 15 x 10 in',
                'intake_weight_lbs' => '',
                'tracking_external' => '1ZPARTIAL0001',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Cambios guardados. El paquete sigue pendiente hasta que se le asigne la agencia.');

        $fresh = $package->fresh();
        $this->assertTrue(ServiceType::isCft($fresh->service_type));
        $this->assertSame('20 X 15 X 10 IN', $fresh->dimension);
        $this->assertSame('PHOTO_PENDING', $fresh->status);
        $this->assertNull($fresh->agency_id);
        $this->assertSame('008001', $fresh->warehouse_code);
    }

    public function test_cubic_feet_still_requires_dimension_when_saving_pending(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending();

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => '',
                'label_name' => '[PENDIENTE]',
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '',
            ])
            ->assertSessionHasErrors('dimension');

        $this->assertSame('AIR', $package->fresh()->service_type);
    }

    public function test_pending_partial_save_to_sea_leaves_auto_air_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending();
        $sack = Consolidation::create([
            'code' => 'SAC-202611-0200',
            'service_type' => 'AIR',
            'status' => 'OPEN',
            'is_auto' => true,
        ]);
        ConsolidationItem::create(['consolidation_id' => $sack->id, 'preregistration_id' => $package->id]);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'label_name' => '[PENDIENTE]',
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '10 x 10 x 10 in',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($package->fresh()->consolidationItem);
    }

    public function test_choosing_agency_still_completes_pending_package(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->pending();

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'CLIENTE PARCIAL',
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '12 x 12 x 12 in',
                'intake_weight_lbs' => 9,
            ])
            ->assertRedirect(route('preregistrations.label', $package->id));

        $fresh = $package->fresh();
        $this->assertSame('RECEIVED_MIAMI', $fresh->status);
        $this->assertSame($agency->id, (int) $fresh->agency_id);
    }

    public function test_completing_pending_package_requires_weight(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->pending();

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'CLIENTE PARCIAL',
                'service_route' => 'AIR',
                'intake_weight_lbs' => '',
            ])
            ->assertSessionHasErrors('intake_weight_lbs');

        $this->assertSame('PHOTO_PENDING', $package->fresh()->status);
    }

    public function test_completed_package_still_requires_agency(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending(['status' => 'RECEIVED_MIAMI', 'label_name' => 'CLIENTE']);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => '',
                'label_name' => 'CLIENTE',
                'service_route' => 'AIR',
                'intake_weight_lbs' => 3,
            ])
            ->assertSessionHasErrors('agency_id');
    }

    public function test_edit_page_marks_agency_and_weight_optional_only_for_pending(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $pending = $this->pending();
        $done = $this->pending([
            'tracking_external' => '1ZPARTIALDONE1',
            'warehouse_code' => '008002',
            'status' => 'RECEIVED_MIAMI',
            'label_name' => 'CLIENTE',
            'agency_id' => $this->agency()->id,
            'intake_weight_lbs' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.edit', $pending->id))
            ->assertOk()
            ->assertSee('opcional mientras está pendiente')
            ->assertSee('obligatorio al completar');

        $this->actingAs($user)
            ->get(route('preregistrations.edit', $done->id))
            ->assertOk()
            ->assertDontSee('opcional mientras está pendiente')
            ->assertDontSee('obligatorio al completar');
    }
}
