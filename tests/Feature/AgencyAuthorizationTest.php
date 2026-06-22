<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyAuthorizationTest extends TestCase
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

    private function createPackage(int $agencyId, string $code = '100001'): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK'.$code,
            'warehouse_code' => $code,
            'label_name' => 'Cliente Test',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'status' => 'READY',
            'agency_id' => $agencyId,
            'ready_at' => now(),
        ]);
    }

    public function test_agency_user_cannot_access_central_modules(): void
    {
        $agencies = $this->createAgencies();
        $user = User::factory()->create(['agency_id' => $agencies['subA']->id]);

        $this->actingAs($user)->get(route('preregistrations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('consolidations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nic-consolidations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('receipt-notes.index'))->assertForbidden();
    }

    public function test_central_user_can_access_central_modules(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)->get(route('preregistrations.index'))->assertOk();
        $this->actingAs($user)->get(route('consolidations.index'))->assertOk();
    }

    public function test_sub_agency_user_can_only_view_own_packages(): void
    {
        $agencies = $this->createAgencies();
        $userA = User::factory()->create(['agency_id' => $agencies['subA']->id]);
        $own = $this->createPackage($agencies['subA']->id, '200001');
        $other = $this->createPackage($agencies['subB']->id, '200002');

        $this->actingAs($userA)->get(route('packages.show', $own->id))->assertOk();
        $this->actingAs($userA)->get(route('packages.show', $other->id))->assertForbidden();
    }

    public function test_main_agency_user_can_view_child_agency_packages(): void
    {
        $agencies = $this->createAgencies();
        $mainUser = User::factory()->create(['agency_id' => $agencies['main']->id]);
        $childPackage = $this->createPackage($agencies['subA']->id, '300001');

        $this->actingAs($mainUser)->get(route('packages.show', $childPackage->id))->assertOk();
    }

    public function test_user_allowed_agency_ids_include_children_for_main_agency(): void
    {
        $agencies = $this->createAgencies();
        $mainUser = User::factory()->create(['agency_id' => $agencies['main']->id]);

        $allowed = $mainUser->allowedAgencyIds();

        $this->assertNotNull($allowed);
        $this->assertContains($agencies['main']->id, $allowed);
        $this->assertContains($agencies['subA']->id, $allowed);
        $this->assertContains($agencies['subB']->id, $allowed);
    }

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get(route('packages.index'))->assertRedirect(route('login'));
        $this->get(route('preregistrations.index'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_admin_route_redirects_to_login(): void
    {
        $this->get(route('agencies.index'))->assertRedirect(route('login'));
    }
}
