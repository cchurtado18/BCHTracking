<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
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
        $this->assertMatchesRegularExpression('/data-only="subagency"[^>]*>[\s\S]*name="logo"/', $html);
        $this->assertStringContainsString('[data-only][hidden] { display: none !important; }', $html);
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

    public function test_preregistration_forms_have_searchable_account_fields(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Norte Express Combo',
            'code' => '0777',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-COMBO-EDIT',
            'label_name' => 'Pendiente',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 1,
            'status' => 'PHOTO_PENDING',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertOk()
            ->assertSee('agency_combobox')
            ->assertSee('slo_client_combobox')
            ->assertSee('Escriba para buscar o baje la lista')
            ->assertSee('Seleccione un servicio')
            ->assertDontSee('id="service_type_post" value="AIR"', false);

        $this->actingAs($user)
            ->get(route('preregistrations.edit', $package->id))
            ->assertOk()
            ->assertSee('agency_combobox')
            ->assertSee('slo_client_combobox')
            ->assertSee('Escriba para buscar o baje la lista');
    }

    public function test_preregistration_requires_choosing_a_service_type(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Servicio',
            'code' => 'SV01',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Sin servicio',
                'intake_weight_lbs' => 2,
                'tracking_external' => 'TRK-NO-SERVICE',
            ])
            ->assertSessionHasErrors('service_type');

        $this->assertDatabaseMissing('preregistrations', [
            'tracking_external' => 'TRK-NO-SERVICE',
        ]);
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

    public function test_client_access_is_edited_inside_clients_module_not_users(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();
        $this->actingAs($admin)->post(route('agencies.store'), [
            'account_type' => 'direct_client',
            'parent_agency_id' => $slo->id,
            'name' => 'Cliente Acceso Edit',
            'user_email' => 'accesoedit@example.com',
            'user_password' => 'password12',
            'user_password_confirmation' => 'password12',
        ])->assertRedirect(route('agencies.index'));

        $client = Agency::query()->where('name', 'Cliente Acceso Edit')->first();
        $this->assertNotNull($client);
        $access = $client->users()->first();
        $this->assertNotNull($access);

        $this->actingAs($admin)
            ->get(route('agencies.show', $client))
            ->assertOk()
            ->assertSee(route('agencies.users.edit', [$client, $access]), false)
            ->assertDontSee(route('users.edit', $access), false);

        $this->actingAs($admin)
            ->get(route('agencies.users.edit', [$client, $access]))
            ->assertOk()
            ->assertSee('Editar acceso del cliente')
            ->assertSee('No se le asignan permisos de la empresa')
            ->assertDontSee('name="is_admin"', false);

        $this->actingAs($admin)
            ->get(route('users.edit', $access))
            ->assertRedirect(route('agencies.users.edit', [$client, $access]));

        $this->actingAs($admin)
            ->put(route('agencies.users.update', [$client, $access]), [
                'name' => 'Nuevo Nombre Acceso',
                'email' => 'nuevoacceso@example.com',
            ])
            ->assertRedirect(route('agencies.show', $client));

        $this->assertDatabaseHas('users', [
            'id' => $access->id,
            'name' => 'Nuevo Nombre Acceso',
            'email' => 'nuevoacceso@example.com',
            'agency_id' => $client->id,
            'is_admin' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('nuevoacceso@example.com');
    }

    public function test_slo_direct_client_ficha_does_not_offer_destinatarios(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente Sin Destinatarios',
            'code' => '0884',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($admin)
            ->get(route('agencies.show', $client))
            ->assertOk()
            ->assertDontSee('Destinatarios (')
            ->assertDontSee('+ Agregar')
            ->assertDontSee('No hay clientes registrados para esta subagencia')
            ->assertSee('Datos de la cuenta y acceso al panel');

        $this->actingAs($admin)
            ->get(route('agency-clients.create', $client))
            ->assertRedirect(route('agencies.show', $client))
            ->assertSessionHas('error');
    }

    public function test_slo_direct_client_audit_blocks_subagency_behaviors(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente Auditoria SLO',
            'code' => '0885',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
            'logo_path' => 'agencies/logos/should-not-show.png',
        ]);
        $access = User::factory()->create([
            'agency_id' => $client->id,
            'is_admin' => false,
            'email' => 'auditoria.slo@example.com',
            'name' => 'Acceso Auditoria',
        ]);

        $this->assertFalse($client->canHaveChildren());
        $this->assertFalse($client->canManageDestinatarios());
        $this->assertTrue($slo->canManageDestinatarios());
        $this->assertSame($slo->id, $client->labelBrandAgency()->id);

        $this->actingAs($admin)
            ->get(route('agencies.show', $client))
            ->assertOk()
            ->assertDontSee('Destinatarios (')
            ->assertDontSee('+ Agregar')
            ->assertDontSee('No hay destinatarios registrados para esta subagencia')
            ->assertDontSee('Credenciales para que la agencia')
            ->assertDontSee('Credenciales para que la subagencia')
            ->assertSee('Credenciales para que el cliente inicie sesión')
            ->assertSee('Editar acceso')
            ->assertDontSee(route('users.edit', $access), false);

        $this->actingAs($admin)
            ->get(route('agency-clients.index', $client))
            ->assertRedirect(route('agencies.show', $client));

        $this->actingAs($admin)
            ->get(route('agency-clients.create', $client))
            ->assertRedirect(route('agencies.show', $client));

        $this->actingAs($admin)
            ->post(route('agency-clients.store', $client), [
                'full_name' => 'Destinatario invalido',
                'phone' => '8888-0000',
            ])
            ->assertRedirect(route('agencies.show', $client));

        $this->assertDatabaseMissing('agency_clients', [
            'agency_id' => $client->id,
            'full_name' => 'Destinatario invalido',
        ]);

        $this->actingAs($admin)
            ->get(route('agencies.edit', $client))
            ->assertOk()
            ->assertDontSee('name="logo"', false)
            ->assertSee('Datos del cliente');

        $this->actingAs($admin)
            ->put(route('agencies.users.update', [$client, $access]), [
                'name' => 'Acceso Auditoria',
                'email' => 'auditoria.slo@example.com',
                'is_admin' => '1',
            ])
            ->assertRedirect(route('agencies.show', $client));

        $this->assertFalse($access->fresh()->is_admin);
        $this->assertSame($client->id, (int) $access->fresh()->agency_id);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('auditoria.slo@example.com');

        $this->actingAs($admin)
            ->get(route('users.edit', $access))
            ->assertRedirect(route('agencies.users.edit', [$client, $access]));

        $this->actingAs($admin)
            ->get(route('agencies.show', $slo))
            ->assertOk()
            ->assertSee('Destinatarios (');
    }

    public function test_direct_client_edit_form_does_not_ask_for_logo(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente sin logo',
            'code' => '0881',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($admin)
            ->get(route('agencies.edit', $client))
            ->assertOk()
            ->assertDontSee('name="logo"', false)
            ->assertSee('Nombre del cliente');
    }

    public function test_slo_direct_client_label_uses_skylink_one_branding(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente Etiqueta SLO',
            'code' => '0882',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);
        $pkg = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-LABEL-SLO-1',
            'warehouse_code' => '882001',
            'label_name' => 'Destinatario Test',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $client->id,
        ]);

        $html = $this->actingAs($user)
            ->get(route('preregistrations.label', $pkg))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('SkyLink One', $html);
        $this->assertStringContainsString((string) $slo->code, $html);
        $this->assertStringNotContainsString('0882 - Cliente Etiqueta SLO', $html);
    }

    public function test_nested_subagency_portal_is_packages_only(): void
    {
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();
        $nested = Agency::create([
            'name' => 'Norte Nested Portal',
            'code' => '0911',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $ch->id,
        ]);
        $sloClient = Agency::create([
            'name' => 'Cliente SLO Portal',
            'code' => '0912',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $nestedUser = User::factory()->create(['agency_id' => $nested->id, 'is_admin' => false]);
        $chUser = User::factory()->create(['agency_id' => $ch->id, 'is_admin' => false]);
        $sloClientUser = User::factory()->create(['agency_id' => $sloClient->id, 'is_admin' => false]);

        $this->assertTrue($nested->fresh()->isNestedUnderPartner());
        $this->assertTrue($nestedUser->isPackagesOnlyPortal());
        $this->assertFalse($ch->fresh()->isNestedUnderPartner());
        $this->assertFalse($chUser->isPackagesOnlyPortal());
        $this->assertFalse($sloClient->fresh()->isNestedUnderPartner());
        $this->assertFalse($sloClientUser->isPackagesOnlyPortal());

        $this->actingAs($nestedUser)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('Mis paquetes')
            ->assertDontSee('>Mis entregas</span>', false)
            ->assertDontSee('>Mis facturas</span>', false);

        $this->actingAs($nestedUser)
            ->get(route('salidas.index'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($nestedUser)
            ->get(route('accounting.invoices.index'))
            ->assertRedirect(route('packages.index'));

        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRKNESTPORTAL1',
            'warehouse_code' => '091101',
            'label_name' => 'Destinatario Norte',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'DELIVERED',
            'agency_id' => $nested->id,
            'ready_at' => now(),
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-NEST-1',
            'agency_id' => $ch->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira Norte',
            'delivery_type' => 'PICKUP',
        ]);

        $this->actingAs($nestedUser)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('SLO-NEST-1')
            ->assertDontSee('Ver hoja');

        $this->actingAs($nestedUser)
            ->get(route('salidas.print-report', ['delivery_note_id' => $note->id]))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($chUser)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('Mis entregas')
            ->assertSee('Mis facturas');

        $this->actingAs($chUser)->get(route('salidas.index'))->assertOk();
        $this->actingAs($chUser)->get(route('accounting.invoices.index'))->assertOk();

        $this->actingAs($sloClientUser)->get(route('accounting.invoices.index'))->assertOk();
        $this->actingAs($sloClientUser)->get(route('salidas.index'))->assertOk();
    }

    public function test_admin_can_reparent_slo_subagency_under_partner(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();
        $misplaced = Agency::create([
            'name' => 'Shipia Express Test',
            'code' => '0919',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $slo->id,
        ]);
        $portalUser = User::factory()->create(['agency_id' => $misplaced->id, 'is_admin' => false]);

        $this->assertFalse($portalUser->isPackagesOnlyPortal());

        $this->actingAs($admin)
            ->get(route('agencies.edit', $misplaced))
            ->assertOk()
            ->assertSee('Hija de otra subagencia')
            ->assertSee($ch->name);

        $this->actingAs($admin)
            ->put(route('agencies.update', $misplaced), [
                'name' => $misplaced->name,
                'subagency_scope' => 'nested',
                'parent_agency_id' => $ch->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('agencies.show', $misplaced));

        $this->assertSame($ch->id, (int) $misplaced->fresh()->parent_agency_id);
        $this->assertTrue($portalUser->fresh()->isPackagesOnlyPortal());
    }

    public function test_admin_cannot_parent_subagency_under_its_child(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['ch' => $ch] = $this->sloTree();
        $child = Agency::create([
            'name' => 'Hija de CH ciclo',
            'code' => '0920',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $ch->id,
        ]);

        $this->actingAs($admin)
            ->put(route('agencies.update', $ch), [
                'name' => $ch->name,
                'subagency_scope' => 'nested',
                'parent_agency_id' => $child->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('parent_agency_id');

        $this->assertNotSame($child->id, (int) $ch->fresh()->parent_agency_id);
    }

    public function test_clients_index_filters_nested_affiliation(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'ch' => $ch] = $this->sloTree();
        $nested = Agency::create([
            'name' => 'Norte Filtro Nested',
            'code' => '0921',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $ch->id,
        ]);
        $sloChild = Agency::create([
            'name' => 'Partner Filtro SLO',
            'code' => '0922',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($admin)
            ->get(route('agencies.index', ['affiliation' => 'nested']))
            ->assertOk()
            ->assertSee('Norte Filtro Nested')
            ->assertDontSee('Partner Filtro SLO');

        $this->actingAs($admin)
            ->get(route('agencies.index', ['affiliation' => 'slo']))
            ->assertOk()
            ->assertSee('Partner Filtro SLO')
            ->assertDontSee('Norte Filtro Nested');
    }

    public function test_direct_client_edit_does_not_offer_subagency_parent(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo] = $this->sloTree();
        $client = Agency::create([
            'name' => 'Cliente sin afiliacion',
            'code' => '0923',
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $this->actingAs($admin)
            ->get(route('agencies.edit', $client))
            ->assertOk()
            ->assertDontSee('Hija de otra subagencia')
            ->assertDontSee('name="subagency_scope"', false);
    }
}
