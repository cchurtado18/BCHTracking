<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_create_page_asks_lbs_or_cubic_when_maritime(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertOk()
            ->assertSee('Seleccione un servicio')
            ->assertSee('Marítimo se cobra')
            ->assertSee('Por libra (lbs)')
            ->assertSee('Por pie cúbico')
            ->assertDontSee('>Pie cúbico</option>', false);
    }

    public function test_maritime_without_billing_choice_is_rejected(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Maritimo',
            'code' => 'SEA1',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Sin cobro',
                'intake_weight_lbs' => 12,
                'tracking_external' => 'TRK-SEA-NO-BILL',
                'service_route' => 'SEA',
            ])
            ->assertSessionHasErrors('sea_billing');

        $this->assertDatabaseMissing('preregistrations', [
            'tracking_external' => 'TRK-SEA-NO-BILL',
        ]);
    }

    public function test_maritime_lbs_stores_sea_and_cubic_stores_cft(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Cobro Mar',
            'code' => 'SEA2',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Por libra',
                'intake_weight_lbs' => 10,
                'tracking_external' => 'TRK-SEA-LBS',
                'service_route' => 'SEA',
                'sea_billing' => 'LBS',
                'photo' => UploadedFile::fake()->image('caja.jpg', 200, 200),
            ]);

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => 'TRK-SEA-LBS',
            'service_type' => 'SEA',
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Por pie',
                'intake_weight_lbs' => 8,
                'tracking_external' => 'TRK-SEA-CFT',
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '12 x 12 x 12',
                'photo' => UploadedFile::fake()->image('tv.jpg', 200, 200),
            ]);

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => 'TRK-SEA-CFT',
            'service_type' => 'CFT',
        ]);
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
