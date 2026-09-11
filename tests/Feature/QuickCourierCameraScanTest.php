<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickCourierCameraScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_courier_page_includes_scan_then_photo_camera(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.quick-courier'))
            ->assertOk()
            ->assertSee('id="cspOverlay"', false)
            ->assertSee('data-csp-build="6"', false)
            ->assertSee('html5-qrcode.min.js')
            ->assertSee('skylinkOpenScanPhotoCamera', false)
            ->assertSee('Aceptar tracking')
            ->assertSee('No es este — seguir buscando')
            ->assertSee('Tomar foto del paquete')
            ->assertSee('Apunte el código de barras del tracking')
            ->assertSee('No lee el código — escribir tracking')
            ->assertSee('id="quickTakePhoto"', false)
            ->assertSee('Si el tracking está vacío');
    }

    public function test_agency_user_cannot_open_quick_courier(): void
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
    }
}
