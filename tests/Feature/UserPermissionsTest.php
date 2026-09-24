<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_ops_user_keeps_warehouse_modules_but_not_sensitive_actions(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false, 'permissions' => null]);

        $this->assertTrue($user->canAccessModule(Permission::MODULE_PACKAGES));
        $this->assertTrue($user->canAccessModule(Permission::MODULE_PREREGISTRATIONS));
        $this->assertFalse($user->hasPermission(Permission::ACTION_DELETE_PREREGISTRATION));
        $this->assertFalse($user->hasPermission(Permission::ACTION_CHANGE_INTAKE_TYPE));
        $this->assertFalse($user->canAccessModule(Permission::MODULE_ACCOUNTING));
        $this->assertFalse($user->canAccessModule(Permission::MODULE_DASHBOARD));
    }

    public function test_empty_permissions_mean_no_modules(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false, 'permissions' => []]);

        $this->assertFalse($user->canAccessModule(Permission::MODULE_PACKAGES));
        $this->assertSame(route('tracking.index', absolute: false), $user->homePath());
    }

    public function test_admin_has_every_permission(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => true]);

        foreach (Permission::allKeys() as $key) {
            $this->assertTrue($user->hasPermission($key), $key);
        }
    }

    public function test_ops_without_module_is_redirected_and_menu_hides_it(): void
    {
        $user = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => [Permission::MODULE_PACKAGES],
        ]);

        $this->actingAs($user)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('>Paquetes</span>', false)
            ->assertDontSee('>Preregistros</span>', false)
            ->assertDontSee('>Facturas</span>', false)
            ->assertDontSee('>Clientes</span>', false);

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($user)
            ->get(route('accounting.invoices.index'))
            ->assertRedirect(route('packages.index'));
    }

    public function test_ops_without_delete_cannot_remove_preregistration_or_see_button(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        $package = $this->createPackage('PHOTO_PENDING');

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Eliminar"', false);

        $this->actingAs($user)
            ->from(route('preregistrations.index'))
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect(route('preregistrations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('preregistrations', ['id' => $package->id, 'deleted_at' => null]);
    }

    public function test_ops_with_delete_can_remove_pending_preregistration(): void
    {
        $user = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => array_merge(Permission::operationalDefaults(), [
                Permission::ACTION_DELETE_PREREGISTRATION,
            ]),
        ]);
        $package = $this->createPackage('PHOTO_PENDING');

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('Eliminar');

        $this->actingAs($user)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect(route('preregistrations.index'));

        $this->assertSoftDeleted('preregistrations', ['id' => $package->id]);
    }

    public function test_admin_can_save_module_and_action_checkboxes(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Módulos')
            ->assertSee('Opciones sensibles')
            ->assertSee('Eliminar preregistro')
            ->assertSee('Cambiar Courier / Drop Off');

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Bodega Uno',
                'email' => 'bodega-uno@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_admin' => '0',
                'permissions' => [
                    Permission::MODULE_PACKAGES,
                    Permission::MODULE_PREREGISTRATIONS,
                    Permission::ACTION_CHANGE_INTAKE_TYPE,
                ],
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'bodega-uno@example.com')->first();
        $this->assertNotNull($created);
        $this->assertFalse($created->is_admin);
        $this->assertSame([
            Permission::MODULE_PACKAGES,
            Permission::MODULE_PREREGISTRATIONS,
            Permission::ACTION_CHANGE_INTAKE_TYPE,
        ], $created->permissions);
        $this->assertTrue($created->hasPermission(Permission::ACTION_CHANGE_INTAKE_TYPE));
        $this->assertFalse($created->hasPermission(Permission::ACTION_DELETE_PREREGISTRATION));
    }

    public function test_admin_user_stores_null_permissions(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jefe',
                'email' => 'jefe@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_admin' => '1',
                'permissions' => [Permission::MODULE_PACKAGES],
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'jefe@example.com')->first();
        $this->assertTrue($created->is_admin);
        $this->assertNull($created->permissions);
        $this->assertTrue($created->hasPermission(Permission::ACTION_DELETE_PREREGISTRATION));
    }

    public function test_ops_user_cannot_open_or_mutate_users_even_with_all_module_keys(): void
    {
        $ops = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => Permission::allKeys(),
        ]);
        $other = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => [Permission::MODULE_PACKAGES],
        ]);

        $this->actingAs($ops)
            ->get(route('users.index'))
            ->assertRedirect(route('packages.index'))
            ->assertDontSee('Nuevo usuario');

        $this->actingAs($ops)
            ->get(route('users.create'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($ops)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertDontSee('>Usuarios</span>', false);

        $this->actingAs($ops)
            ->post(route('users.store'), [
                'name' => 'Intruso',
                'email' => 'intruso@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_admin' => '1',
                'permissions' => Permission::allKeys(),
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);

        $this->actingAs($ops)
            ->put(route('users.update', $ops), [
                'name' => $ops->name,
                'email' => $ops->email,
                'is_admin' => '1',
                'permissions' => Permission::allKeys(),
            ])
            ->assertRedirect(route('packages.index'));

        $ops->refresh();
        $this->assertFalse($ops->is_admin);

        $this->actingAs($ops)
            ->put(route('users.update', $other), [
                'name' => $other->name,
                'email' => $other->email,
                'is_admin' => '1',
                'permissions' => [Permission::MODULE_ACCOUNTING],
            ])
            ->assertRedirect(route('packages.index'));

        $other->refresh();
        $this->assertFalse($other->is_admin);
        $this->assertSame([Permission::MODULE_PACKAGES], $other->permissions);
    }

    public function test_agency_user_cannot_access_users_module(): void
    {
        $agency = Agency::create([
            'name' => 'Agencia Portal',
            'code' => 'G'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);
        $user = User::factory()->create(['agency_id' => $agency->id, 'is_admin' => false]);

        $this->actingAs($user)
            ->get(route('users.create'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Cliente Admin',
                'email' => 'cliente-admin@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_admin' => '1',
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertDatabaseMissing('users', ['email' => 'cliente-admin@example.com']);
    }

    public function test_password_change_cannot_inject_admin_or_permissions(): void
    {
        $ops = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => [Permission::MODULE_PACKAGES],
        ]);

        $this->actingAs($ops)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_admin' => '1',
                'permissions' => Permission::allKeys(),
            ]);

        $ops->refresh();
        $this->assertFalse($ops->is_admin);
        $this->assertSame([Permission::MODULE_PACKAGES], $ops->permissions);
        $this->assertFalse($ops->canAccessModule(Permission::MODULE_ACCOUNTING));
    }

    public function test_admin_can_update_existing_ops_permissions(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $ops = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => Permission::operationalDefaults(),
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $ops), [
                'name' => $ops->name,
                'email' => $ops->email,
                'is_admin' => '0',
                'permissions' => [
                    Permission::MODULE_PACKAGES,
                    Permission::ACTION_DELETE_PREREGISTRATION,
                ],
            ])
            ->assertRedirect(route('users.index'));

        $ops->refresh();
        $this->assertSame([
            Permission::MODULE_PACKAGES,
            Permission::ACTION_DELETE_PREREGISTRATION,
        ], $ops->permissions);
        $this->assertTrue($ops->hasPermission(Permission::ACTION_DELETE_PREREGISTRATION));
        $this->assertFalse($ops->canAccessModule(Permission::MODULE_PREREGISTRATIONS));
    }

    private function createPackage(string $status = 'RECEIVED_MIAMI'): Preregistration
    {
        $agency = Agency::create([
            'name' => 'Agencia Permisos',
            'code' => 'P'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);

        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-PERM-'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(550011, 559999),
            'label_name' => 'Cliente Permisos',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'status' => $status,
            'agency_id' => $agency->id,
        ]);
    }
}
