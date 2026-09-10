<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreregistrationCreateMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_avoids_silent_mobile_submit_blockers(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertOk()
            ->assertSee('id="preregForm" novalidate', false)
            ->assertSee('skylinkResolvePreregAgency', false)
            ->assertSee('pointerdown')
            ->assertSee('Seleccione la subagencia o el cliente de SkyLink One', false);
    }

    public function test_ajax_dropoff_create_returns_json_redirect(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Dropoff Movil',
            'code' => 'DM01',
            'is_active' => true,
            'is_main' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'DROP_OFF',
                'agency_id' => $agency->id,
                'label_name' => 'Maria Lopez',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 2.5,
                'dimension' => '10 x 8 x 5 in',
                'photo' => UploadedFile::fake()->image('caja.jpg', 400, 400),
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()->assertJsonStructure(['redirect_url', 'message']);
        $this->assertStringContainsString('/preregistrations/', (string) $response->json('redirect_url'));
        $this->assertDatabaseHas('preregistrations', [
            'intake_type' => 'DROP_OFF',
            'label_name' => 'MARIA LOPEZ',
            'agency_id' => $agency->id,
        ]);
        $this->assertNotNull(Preregistration::where('agency_id', $agency->id)->first()?->warehouse_code);
    }
}
