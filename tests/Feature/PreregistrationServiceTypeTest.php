<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreregistrationServiceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_lets_you_change_air_to_sea_on_pending_package(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('PHOTO_PENDING', 'AIR');

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('Tipo de servicio')
            ->assertSee('Guardar');

        $this->actingAs($user)
            ->patch(route('preregistrations.service-type', $package->id), [
                'service_type' => 'SEA',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('success');

        $this->assertSame('SEA', $package->fresh()->service_type);
    }

    public function test_cannot_change_service_once_in_transit(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('IN_TRANSIT', 'AIR');

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->patch(route('preregistrations.service-type', $package->id), [
                'service_type' => 'SEA',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('error');

        $this->assertSame('AIR', $package->fresh()->service_type);
    }

    private function createPackage(string $status, string $service): Preregistration
    {
        $agency = Agency::create([
            'name' => 'Agencia Servicio Vista',
            'code' => 'SV'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);

        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRKSVC'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(880011, 889999),
            'label_name' => '[PENDIENTE]',
            'service_type' => $service,
            'intake_weight_lbs' => 2,
            'status' => $status,
            'agency_id' => $agency->id,
        ]);
    }
}
