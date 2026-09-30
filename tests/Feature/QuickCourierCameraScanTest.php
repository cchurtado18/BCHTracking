<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickCourierCameraScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_courier_page_opens_native_camera_only(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.quick-courier'))
            ->assertOk()
            ->assertDontSee('id="cspOverlay"', false)
            ->assertDontSee('skylinkOpenScanPhotoCamera', false)
            ->assertSee('Tomar foto')
            ->assertSee('Detener cámara')
            ->assertSee('Tome la foto del paquete y, si quiere, el tracking')
            ->assertSee('id="quickTakePhoto"', false)
            ->assertSee('Peso (lb)')
            ->assertSee('id="intake_weight_lbs"', false)
            ->assertSee('Servicio')
            ->assertSee('id="service_type"', false)
            ->assertSee('value="AIR" selected', false)
            ->assertSee('Aéreo')
            ->assertSee('Marítimo');
    }

    public function test_tracking_photo_page_includes_scan_then_photo_camera(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.tracking-photo'))
            ->assertOk()
            ->assertSee('id="cspOverlay"', false)
            ->assertSee('data-csp-build="23"', false)
            ->assertSee('startScanLoop', false)
            ->assertSee('drawGuideBand', false)
            ->assertSee('drawQrBox', false)
            ->assertSee('extractFromUrl', false)
            ->assertSee('isUpsTracking', false)
            ->assertSee('isAmazonTracking', false)
            ->assertSee('padQuietZone', false)
            ->assertSee('coverMappedCrop', false)
            ->assertSee('F.CODE_128, F.QR_CODE, F.DATA_MATRIX', false)
            ->assertSee('canonicalTracking', false)
            ->assertSee('trackingFromRaw', false)
            ->assertSee('stripRoutingPrefix', false)
            ->assertSee('Abrir cámara')
            ->assertDontSee('Fotografiar el código')
            ->assertSee('/vendor/html5-qrcode.min.js')
            ->assertSee('html5-qrcode.min.js')
            ->assertSee('skylinkOpenScanPhotoCamera', false)
            ->assertSee('Usar este tracking')
            ->assertSee('No es este — seguir buscando')
            ->assertSee('Tomar foto del paquete')
            ->assertSee('Apunte el código de barras o el QR del tracking')
            ->assertSee('Tracking (se llena al escanear)')
            ->assertSee('id="quickTakePhoto"', false)
            ->assertSee('Si el tracking está vacío')
            ->assertSee('Peso (lb)')
            ->assertSee('id="intake_weight_lbs"', false)
            ->assertSee('Servicio')
            ->assertSee('id="service_type"', false)
            ->assertSee('value="AIR" selected', false)
            ->assertSee('Aéreo')
            ->assertSee('Marítimo');
    }

    public function test_quick_courier_store_requires_and_saves_package_weight(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZWEIGHTTEST001',
                'service_type' => 'AIR',
                'photos' => [$photo],
            ])
            ->assertSessionHasErrors('intake_weight_lbs');

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZWEIGHTTEST001',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 12.75,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => '1ZWEIGHTTEST001',
            'intake_weight_lbs' => 12.75,
            'service_type' => 'AIR',
            'status' => 'PHOTO_PENDING',
        ]);
        $created = Preregistration::where('tracking_external', '1ZWEIGHTTEST001')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->warehouse_code);
    }

    public function test_quick_courier_store_defaults_to_air_and_can_save_sea(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZSERVICETEST001',
                'intake_weight_lbs' => 4.5,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => '1ZSERVICETEST001',
            'service_type' => 'AIR',
            'status' => 'PHOTO_PENDING',
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZSERVICETEST002',
                'service_type' => 'CFT',
                'intake_weight_lbs' => 4.5,
                'photos' => [$photo],
            ])
            ->assertSessionHasErrors('service_type');

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZSERVICETEST002',
                'service_type' => 'SEA',
                'intake_weight_lbs' => 4.5,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => '1ZSERVICETEST002',
            'service_type' => 'SEA',
            'intake_weight_lbs' => 4.5,
            'status' => 'PHOTO_PENDING',
        ]);
    }

    public function test_preregistration_index_shows_both_capture_buttons(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertSee('Captura rápida Courier')
            ->assertSee('Captura tracking + foto');
    }

    public function test_agency_user_cannot_open_quick_courier_or_tracking_photo(): void
    {
        $agency = Agency::create([
            'name' => 'Subagencia Camara',
            'code' => 'CAM1',
            'is_active' => true,
            'is_main' => false,
        ]);
        $user = User::factory()->create(['agency_id' => $agency->id]);

        $this->actingAs($user)
            ->get(route('preregistrations.quick-courier'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($user)
            ->get(route('preregistrations.tracking-photo'))
            ->assertRedirect(route('packages.index'));
    }
}
