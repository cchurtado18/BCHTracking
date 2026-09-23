<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrealertModuleTest extends TestCase
{
    use RefreshDatabase;

    private function createAgencies(): array
    {
        $suffix = (string) random_int(1000, 9999);

        $main = Agency::create([
            'name' => 'Main '.$suffix,
            'code' => 'M'.$suffix,
            'phone' => '111',
            'is_active' => true,
            'is_main' => true,
        ]);

        $subA = Agency::create([
            'name' => 'Sub A '.$suffix,
            'code' => 'A'.$suffix,
            'phone' => '222',
            'is_active' => true,
            'is_main' => false,
            'parent_agency_id' => $main->id,
        ]);

        $subB = Agency::create([
            'name' => 'Sub B '.$suffix,
            'code' => 'B'.$suffix,
            'phone' => '333',
            'is_active' => true,
            'is_main' => false,
            'parent_agency_id' => $main->id,
        ]);

        return compact('main', 'subA', 'subB');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('prealerts.index'))->assertRedirect(route('login'));
        $this->get(route('prealerts.create'))->assertRedirect(route('login'));
    }

    public function test_agency_user_can_open_prealert_module(): void
    {
        $agencies = $this->createAgencies();
        $user = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        $this->actingAs($user)
            ->get(route('prealerts.index'))
            ->assertOk()
            ->assertSee('Prealerta')
            ->assertSee('Nueva prealerta');

        $this->actingAs($user)
            ->get(route('prealerts.create'))
            ->assertOk()
            ->assertSee('Nombre')
            ->assertSee('Agencia')
            ->assertSee('Tracking')
            ->assertSee('Servicio')
            ->assertSee('Aéreo')
            ->assertSee('Marítimo')
            ->assertDontSee('Pie cúbico')
            ->assertSee('Descripción');
    }

    public function test_central_user_can_open_prealert_module(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('prealerts.index'))
            ->assertOk()
            ->assertSee('Nueva prealerta');
    }

    public function test_agency_user_can_create_prealert_for_own_agency(): void
    {
        $agencies = $this->createAgencies();
        $user = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        $this->actingAs($user)
            ->post(route('prealerts.store'), [
                'name' => 'Nathalia Torrez',
                'agency_id' => $agencies['subA']->id,
                'tracking' => 'spxmia012462609040002615',
                'service_type' => 'AIR',
                'description' => 'Caja de ropa',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('prealerts', [
            'name' => 'NATHALIA TORREZ',
            'agency_id' => $agencies['subA']->id,
            'tracking' => 'SPXMIA012462609040002615',
            'service_type' => 'AIR',
            'description' => 'CAJA DE ROPA',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
    }

    public function test_agency_user_cannot_create_prealert_for_other_agency(): void
    {
        $agencies = $this->createAgencies();
        $user = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        $this->actingAs($user)
            ->post(route('prealerts.store'), [
                'name' => 'Cliente B',
                'agency_id' => $agencies['subB']->id,
                'tracking' => 'SPXMIA999999999999',
                'service_type' => 'SEA',
                'description' => 'Test',
            ])
            ->assertSessionHasErrors('agency_id');

        $this->assertDatabaseCount('prealerts', 0);
    }

    public function test_agency_user_cannot_view_other_agency_prealert(): void
    {
        $agencies = $this->createAgencies();
        $userA = User::factory()->create(['agency_id' => $agencies['subA']->id]);
        $userB = User::factory()->create(['agency_id' => $agencies['subB']->id]);

        $prealert = Prealert::create([
            'name' => 'Cliente B',
            'agency_id' => $agencies['subB']->id,
            'tracking' => 'SPXMIA888888888888',
            'service_type' => 'SEA',
            'description' => 'Otro',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $userB->id,
        ]);

        $this->actingAs($userA)
            ->get(route('prealerts.show', $prealert))
            ->assertForbidden();
    }

    public function test_tracking_must_be_unique(): void
    {
        $agencies = $this->createAgencies();
        $user = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        Prealert::create([
            'name' => 'Uno',
            'agency_id' => $agencies['subA']->id,
            'tracking' => 'SPXMIA111111111111',
            'service_type' => 'AIR',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('prealerts.store'), [
                'name' => 'Dos',
                'agency_id' => $agencies['subA']->id,
                'tracking' => 'SPXMIA111111111111',
                'service_type' => 'AIR',
            ])
            ->assertSessionHasErrors('tracking');
    }

    public function test_sidebar_shows_prealert_for_agency_and_central(): void
    {
        $agencies = $this->createAgencies();
        $agencyUser = User::factory()->create(['agency_id' => $agencies['subA']->id]);
        $central = User::factory()->create(['agency_id' => null]);

        $this->actingAs($agencyUser)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('>Prealerta</span>', false);

        $this->actingAs($central)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('>Prealerta</span>', false);
    }

    public function test_warehouse_lookup_returns_open_prealert(): void
    {
        $agencies = $this->createAgencies();
        $central = User::factory()->create(['agency_id' => null]);
        $agencyUser = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        Prealert::create([
            'name' => 'Nathalia Torrez',
            'agency_id' => $agencies['subA']->id,
            'tracking' => 'SPXMIA012462609040002615',
            'service_type' => 'AIR',
            'description' => 'Caja de ropa',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $agencyUser->id,
        ]);

        $this->actingAs($central)
            ->getJson(route('prealerts.lookup', ['tracking' => 'spxmia012462609040002615']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'prealert' => [
                    'tracking' => 'SPXMIA012462609040002615',
                    'name' => 'NATHALIA TORREZ',
                    'service_type' => 'AIR',
                    'service_label' => 'Aéreo',
                ],
            ]);

        $this->actingAs($central)
            ->getJson(route('prealerts.lookup', ['tracking' => 'UNKNOWNTRACK12']))
            ->assertOk()
            ->assertJson(['found' => false]);

        $this->actingAs($agencyUser)
            ->getJson(route('prealerts.lookup', ['tracking' => 'SPXMIA012462609040002615']))
            ->assertForbidden();
    }

    public function test_warehouse_lookup_matches_usps_prefixed_barcode(): void
    {
        $agencies = $this->createAgencies();
        $central = User::factory()->create(['agency_id' => null]);
        $agencyUser = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        Prealert::create([
            'name' => 'Aiman Usps',
            'agency_id' => $agencies['subA']->id,
            'tracking' => '42033142940011189956253786216799',
            'service_type' => 'AIR',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $agencyUser->id,
        ]);

        $this->actingAs($central)
            ->getJson(route('prealerts.lookup', ['tracking' => '9400111899562537862167']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'prealert' => [
                    'tracking' => '9400111899562537862167',
                    'name' => 'AIMAN USPS',
                ],
            ]);
    }

    public function test_intake_pages_include_prealert_lookup(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertOk()
            ->assertSee('id="prealertNotice"', false)
            ->assertSee('Este paquete ya fue prealertado');

        $this->actingAs($user)
            ->get(route('preregistrations.tracking-photo'))
            ->assertOk()
            ->assertSee('id="prealertNotice"', false)
            ->assertSee('id="cspPrealert"', false);
    }

    public function test_quick_courier_store_marks_prealert_as_matched(): void
    {
        Storage::fake('public');
        $agencies = $this->createAgencies();
        $central = User::factory()->create(['agency_id' => null]);
        $prealert = Prealert::create([
            'name' => 'Cliente Prealerta',
            'agency_id' => $agencies['subA']->id,
            'tracking' => 'SPXMIA777777777777',
            'service_type' => 'SEA',
            'description' => 'Zapatos',
            'status' => Prealert::STATUS_PENDING,
        ]);

        $this->actingAs($central)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => 'SPXMIA777777777777',
                'intake_weight_lbs' => 4.5,
                'photos' => [UploadedFile::fake()->image('caja.jpg', 400, 400)],
            ])
            ->assertRedirect();

        $this->assertSame(Prealert::STATUS_MATCHED, $prealert->fresh()->status);
        $this->assertNotNull($prealert->fresh()->preregistration_id);
        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => 'SPXMIA777777777777',
            'service_type' => 'SEA',
            'agency_id' => $agencies['subA']->id,
            'intake_weight_lbs' => 4.5,
        ]);

        $package = Preregistration::where('tracking_external', 'SPXMIA777777777777')->first();
        $this->actingAs($central)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('Este paquete ya fue prealertado')
            ->assertSee('CLIENTE PREALERTA')
            ->assertSee('Marítimo');
    }

    public function test_preregistration_store_marks_prealert_as_matched(): void
    {
        $agencies = $this->createAgencies();
        $central = User::factory()->create(['agency_id' => null]);
        $prealert = Prealert::create([
            'name' => 'Ana Lopez',
            'agency_id' => $agencies['subA']->id,
            'tracking' => 'SPXMIA666666666666',
            'service_type' => 'SEA',
            'status' => Prealert::STATUS_PENDING,
        ]);

        $this->actingAs($central)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agencies['subA']->id,
                'label_name' => 'Ana Lopez',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 2,
                'tracking_external' => 'SPXMIA666666666666',
            ])
            ->assertRedirect();

        $this->assertSame(Prealert::STATUS_MATCHED, $prealert->fresh()->status);
        $this->assertNotNull($prealert->fresh()->preregistration_id);
    }
}
