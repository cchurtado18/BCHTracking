<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function sloTree(): array
    {
        $slo = Agency::query()->where('code', '0001')->first()
            ?? Agency::query()->where('name', 'SkyLink One')->first();
        $ch = Agency::query()->where('code', '0002')->first()
            ?? Agency::query()->where('name', 'CH LOGISTICS')->first();

        $this->assertNotNull($slo);
        $this->assertNotNull($ch);

        return compact('slo', 'ch');
    }

    public function test_admin_can_create_nested_subagency_and_slo_direct_client_with_login(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'subagency',
            'parent_agency_id' => $ch->id,
            'name' => 'Sub de CH Test',
            'user_email' => 'subch@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', [
            'name' => 'Sub de CH Test',
            'parent_agency_id' => $ch->id,
            'account_type' => 'subagency',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'subch@example.com',
        ]);

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Cliente Directo SLO',
            'user_email' => 'clienteslo@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', [
            'name' => 'Cliente Directo SLO',
            'parent_agency_id' => $slo->id,
            'account_type' => 'direct_client',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'clienteslo@example.com',
        ]);

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'subagency',
            'parent_agency_id' => $slo->id,
            'name' => 'Sub de SLO Test',
            'user_email' => 'subslo@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', [
            'name' => 'Sub de SLO Test',
            'parent_agency_id' => $slo->id,
            'account_type' => 'subagency',
        ]);
    }

    public function test_direct_client_always_belongs_to_slo_even_if_parent_is_sent(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $ch->id,
            'name' => 'Cliente ignora padre',
            'user_email' => 'ignora@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', [
            'name' => 'Cliente ignora padre',
            'parent_agency_id' => $slo->id,
            'account_type' => 'direct_client',
        ]);
    }

    public function test_create_client_form_lists_any_agency_as_parent_and_fixes_slo_for_direct_client(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();

        $html = $this->actingAs($admin)->get(route('agencies.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="parent_field_wrap"', $html);
        $this->assertStringContainsString('id="parent_agency_id_slo"', $html);
        $this->assertStringContainsString((string) $slo->id, $html);
        $this->assertStringContainsString((string) $ch->id, $html);
        $this->assertStringContainsString('name="account_type"', $html);
        $this->assertStringContainsString('value="direct_client"', $html);
        $this->assertStringContainsString('Nombre del cliente', $html);
        $this->assertStringContainsString('name="subagency_scope"', $html);
        $this->assertStringContainsString('Nueva subagencia de SLO', $html);
        $this->assertStringContainsString('Subagencia de otra subagencia', $html);
    }

    public function test_clients_index_uses_list_layout(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['ch' => $ch] = $this->sloTree();

        $this->actingAs($admin)
            ->get(route('agencies.index'))
            ->assertOk()
            ->assertSee('Clientes')
            ->assertSee('Nuevo cliente')
            ->assertSee($ch->name);
    }

    public function test_subagency_scope_slo_assigns_skylink_one_as_parent(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'subagency',
            'subagency_scope' => 'slo',
            'name' => 'Sub SLO por alcance',
            'phone' => '8888-0000',
            'user_email' => 'alcance@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', [
            'name' => 'Sub SLO por alcance',
            'parent_agency_id' => $slo->id,
            'account_type' => 'subagency',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'alcance@example.com',
        ]);
    }

    public function test_direct_client_cannot_be_parent_of_subagency(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();

        $direct = Agency::create([
            'name' => 'Cliente hoja',
            'code' => '0099',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'subagency',
            'parent_agency_id' => $direct->id,
            'name' => 'Hija invalida',
            'user_email' => 'invalida@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertSessionHasErrors('parent_agency_id');
    }

    public function test_ch_user_sees_descendant_packages_and_delivery_network_includes_children(): void
    {
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();
        $child = Agency::create([
            'name' => 'Hija CH',
            'code' => '0100',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $ch->id,
        ]);

        $chUser = User::factory()->create(['agency_id' => $ch->id]);
        $childPkg = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRKCHILD1',
            'warehouse_code' => '880001',
            'label_name' => 'Destinatario',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4,
            'status' => 'READY',
            'agency_id' => $child->id,
            'ready_at' => now(),
        ]);

        $this->actingAs($chUser)->get(route('packages.show', $childPkg->id))->assertOk();

        $network = $ch->deliveryNetworkIds();
        $this->assertContains($ch->id, $network);
        $this->assertContains($child->id, $network);
        $this->assertNotContains($slo->id, $network);
        $this->assertTrue($child->isChLogistics());
    }

    public function test_preregistration_cannot_assign_package_to_slo_root(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        ['slo' => $slo] = $this->sloTree();

        $this->actingAs($user)->post(route('preregistrations.store'), [
            'intake_type' => 'COURIER',
            'agency_id' => $slo->id,
            'label_name' => 'Juan Perez',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'tracking_external' => 'TRK-SLO-ROOT-1',
        ])->assertSessionHasErrors('agency_id');
    }

    public function test_preregistration_assigns_package_to_slo_direct_client(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente Facturable SLO',
            'code' => '0888',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($user)->post(route('preregistrations.store'), [
            'intake_type' => 'COURIER',
            'agency_id' => $client->id,
            'label_name' => 'Juan Perez',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'tracking_external' => 'TRK-SLO-CLIENT-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('preregistrations', [
            'tracking_external' => 'TRK-SLO-CLIENT-1',
            'agency_id' => $client->id,
        ]);
    }

    public function test_cannot_create_client_with_email_already_used_by_a_user(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        User::factory()->create([
            'name' => 'Carlos Hurtado',
            'email' => 'cchurtadomora@gmail.com',
            'agency_id' => null,
            'is_admin' => false,
        ]);
        ['slo' => $slo] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Cliente correo repetido',
            'user_email' => 'CCHURTADOMORA@gmail.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertSessionHasErrors('user_email');

        $this->assertStringContainsString(
            'Carlos Hurtado',
            session('errors')->first('user_email')
        );
        $this->assertDatabaseMissing('agencies', ['name' => 'Cliente correo repetido']);
    }

    public function test_deleting_client_frees_login_email_for_reuse(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Cliente temporal',
            'user_email' => 'temporal@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $agency = Agency::query()->where('name', 'Cliente temporal')->first();
        $this->assertNotNull($agency);
        $this->assertDatabaseHas('users', ['email' => 'temporal@example.com', 'agency_id' => $agency->id]);

        $this->actingAs($admin)
            ->delete(route('agencies.destroy', $agency))
            ->assertRedirect(route('agencies.index'));

        $this->assertDatabaseMissing('users', ['email' => 'temporal@example.com']);

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Cliente reusa correo',
            'user_email' => 'temporal@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $this->assertDatabaseHas('agencies', ['name' => 'Cliente reusa correo']);
        $this->assertDatabaseHas('users', ['email' => 'temporal@example.com']);
    }

    public function test_slo_direct_client_can_login_and_see_own_packages(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();

        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Vanesa Sanchez',
            'user_email' => 'Vanesa@primetrackgroup.com',
            'user_name' => 'Vanesa@primetrackgroup.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $agency = Agency::query()->where('name', 'Vanesa Sanchez')->first();
        $this->assertNotNull($agency);
        $this->assertDatabaseHas('users', [
            'email' => 'vanesa@primetrackgroup.com',
            'agency_id' => $agency->id,
            'name' => 'Vanesa Sanchez',
        ]);

        $this->post('/logout');

        $this->get(route('preregistrations.index'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'VANESA@primetrackgroup.com',
            'password' => 'password12',
        ])->assertRedirect(route('packages.index'));

        $this->assertAuthenticated();
        $this->get(route('packages.index'))
            ->assertOk()
            ->assertSee('Mis paquetes')
            ->assertDontSee('name="intake_type"', false)
            ->assertDontSee('Reporte PDF')
            ->assertDontSee('Procesar en almacén NIC');
        $this->get(route('dashboard'))->assertRedirect(route('packages.index'));
        $this->get(route('preregistrations.index'))->assertRedirect(route('packages.index'));
        $this->get(route('time-entries.index'))->assertRedirect(route('packages.index'));
    }
}
