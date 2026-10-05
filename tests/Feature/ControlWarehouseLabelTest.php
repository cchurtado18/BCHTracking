<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControlWarehouseLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_maritime_quick_capture_assigns_warehouse_and_opens_control_label(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $response = $this->actingAs($user)
            ->postJson(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZCTRLSEA001',
                'service_type' => 'SEA',
                'intake_weight_lbs' => 8.25,
                'photos' => [$photo],
            ])
            ->assertOk();

        $package = Preregistration::where('tracking_external', '1ZCTRLSEA001')->first();
        $this->assertNotNull($package);
        $this->assertSame('PHOTO_PENDING', $package->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $package->warehouse_code);
        $response->assertJsonPath('redirect_url', route('preregistrations.control-label', $package->id));
    }

    public function test_air_quick_capture_does_not_assign_warehouse(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $response = $this->actingAs($user)
            ->postJson(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZCTRLAIR001',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 8.25,
                'photos' => [$photo],
            ])
            ->assertOk();

        $package = Preregistration::where('tracking_external', '1ZCTRLAIR001')->first();
        $this->assertNotNull($package);
        $this->assertNull($package->warehouse_code);
        $response->assertJsonPath('redirect_url', route('preregistrations.show', $package->id));
    }

    public function test_control_label_shows_only_logo_and_warehouse_barcode(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '654321',
            'tracking_external' => '1ZCTRLHIDE001',
            'label_name' => '[PENDIENTE]',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.control-label', $package->id))
            ->assertOk()
            ->assertSee('PrimeTrack Group', false)
            ->assertSee('primetrack-group-logo.png', false)
            ->assertSee('8307 NW 68th St', false)
            ->assertSee('Miami, FL 33166', false)
            ->assertSee($package->created_at->timezone(config('app.display_timezone') ?: 'America/New_York')->format('d/m/Y'), false)
            ->assertSee('654321', false)
            ->assertSee('data-barcode="654321"', false)
            ->assertSee('barcode-'.$package->id.'-control', false)
            ->assertDontSee('1ZCTRLHIDE001', false)
            ->assertDontSee('[PENDIENTE]', false)
            ->assertDontSee('TRACKING GLOBAL', false)
            ->assertDontSee('AGENCIA', false)
            ->assertDontSee('sl-header-agency', false);
    }

    public function test_main_label_redirects_pending_package_to_control_label(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '112233',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.label', $package->id))
            ->assertRedirect(route('preregistrations.control-label', $package->id));
    }

    public function test_completing_maritime_pending_keeps_the_same_warehouse(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '778899',
            'tracking_external' => '1ZCTRLKEEP001',
        ]);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'DESTINATARIO MAR',
                'service_type' => 'SEA',
                'service_route' => 'SEA',
                'sea_billing' => 'LBS',
                'intake_weight_lbs' => 8.25,
                'tracking_external' => $package->tracking_external,
            ])
            ->assertRedirect(route('preregistrations.label', $package->id));

        $package->refresh();
        $this->assertSame('RECEIVED_MIAMI', $package->status);
        $this->assertSame('778899', $package->warehouse_code);
    }

    public function test_pending_show_and_index_open_control_label(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '445566',
            'tracking_external' => '1ZCTRLSHOW001',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('Etiqueta de control 4×6', false)
            ->assertSee(route('preregistrations.control-label', $package->id), false)
            ->assertDontSee('>Etiqueta 4×6<', false);

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertSee(route('preregistrations.control-label', $package->id), false);
    }

    public function test_narrow_control_label_opens(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '990011',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.control-label', ['id' => $package->id, 'format' => 'narrow']))
            ->assertOk()
            ->assertSee('2.25in 4in', false)
            ->assertSee('990011', false);
    }

    public function test_guest_cannot_open_control_label(): void
    {
        $package = $this->pendingMaritimePackage([
            'warehouse_code' => '101010',
        ]);

        $this->get(route('preregistrations.control-label', $package->id))
            ->assertRedirect(route('login'));
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pendingMaritimePackage(array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZCTRL'.random_int(100000, 999999),
            'warehouse_code' => '123456',
            'label_name' => '[PENDIENTE]',
            'service_type' => 'SEA',
            'intake_weight_lbs' => 8.25,
            'status' => 'PHOTO_PENDING',
            'agency_id' => null,
        ], $overrides));
    }
}
