<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingTrackingEditTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZPENDINGOLD01',
            'warehouse_code' => '007001',
            'label_name' => '[PENDIENTE]',
            'service_type' => 'AIR',
            'status' => 'PHOTO_PENDING',
        ], $overrides));
    }

    public function test_pending_package_tracking_can_be_corrected_and_stays_pending(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending();

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('id="tracking_external_show"', false)
            ->assertSee('Guardar tracking');

        $this->actingAs($user)
            ->patch(route('preregistrations.tracking', $package->id), [
                'tracking_external' => ' 1zpendingnew01 ',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('success', 'Tracking actualizado.');

        $fresh = $package->fresh();
        $this->assertSame('1ZPENDINGNEW01', $fresh->tracking_external);
        $this->assertSame('PHOTO_PENDING', $fresh->status);
        $this->assertSame('[PENDIENTE]', $fresh->label_name);
    }

    public function test_pending_package_tracking_can_be_added_when_it_had_none(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending(['tracking_external' => null]);

        $this->actingAs($user)
            ->patch(route('preregistrations.tracking', $package->id), [
                'tracking_external' => '1ZPENDINGADD01',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('1ZPENDINGADD01', $package->fresh()->tracking_external);
    }

    public function test_tracking_used_by_another_package_is_rejected(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $this->pending(['tracking_external' => '1ZTAKEN000001', 'warehouse_code' => '007002']);
        $package = $this->pending();

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->patch(route('preregistrations.tracking', $package->id), [
                'tracking_external' => '1ZTAKEN000001',
            ])
            ->assertSessionHasErrors('tracking_external');

        $this->assertSame('1ZPENDINGOLD01', $package->fresh()->tracking_external);
    }

    public function test_completed_package_cannot_use_quick_tracking_edit(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pending(['status' => 'RECEIVED_MIAMI', 'label_name' => 'CLIENTE']);

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertDontSee('id="tracking_external_show"', false);

        $this->actingAs($user)
            ->patch(route('preregistrations.tracking', $package->id), [
                'tracking_external' => '1ZCHANGED0001',
            ])
            ->assertSessionHas('error');

        $this->assertSame('1ZPENDINGOLD01', $package->fresh()->tracking_external);
    }

    public function test_corrected_tracking_links_open_prealert(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Prealerta Pendiente',
            'code' => 'PP01',
            'phone' => '555-0199',
            'is_active' => true,
            'is_main' => false,
        ]);
        $prealert = Prealert::create([
            'name' => 'Cliente Corregido',
            'agency_id' => $agency->id,
            'tracking' => 'SPXMIA555555555555',
            'service_type' => 'AIR',
            'status' => Prealert::STATUS_PENDING,
        ]);
        $package = $this->pending();

        $this->actingAs($user)
            ->patch(route('preregistrations.tracking', $package->id), [
                'tracking_external' => 'SPXMIA555555555555',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Prealert::STATUS_MATCHED, $prealert->fresh()->status);
        $this->assertSame($package->id, (int) $prealert->fresh()->preregistration_id);
    }

    public function test_guest_cannot_edit_tracking(): void
    {
        $package = $this->pending();

        $this->patch(route('preregistrations.tracking', $package->id), [
            'tracking_external' => '1ZGUEST000001',
        ])->assertRedirect(route('login'));

        $this->assertSame('1ZPENDINGOLD01', $package->fresh()->tracking_external);
    }
}
