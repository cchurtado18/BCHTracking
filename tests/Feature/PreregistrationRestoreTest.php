<?php

namespace Tests\Feature;

use App\Models\Preregistration;
use App\Models\User;
use App\Services\PreregistrationPhotoService;
use App\Support\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreregistrationRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_preregistration_disappears_from_the_list(): void
    {
        $user = $this->opsUser();
        $package = $this->createPackage();

        $this->actingAs($user)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect(route('preregistrations.index'));

        $this->actingAs($user)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertDontSee($package->tracking_external);
    }

    public function test_ops_cannot_open_trash_or_restore(): void
    {
        $user = $this->opsUser();
        $package = $this->createPackage();
        $package->delete();

        $this->actingAs($user)
            ->get(route('audit.trashed'))
            ->assertRedirect(route('packages.index'));

        $this->actingAs($user)
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect(route('packages.index'));

        $this->assertSoftDeleted('preregistrations', ['id' => $package->id]);
    }

    public function test_admin_can_restore_a_deleted_preregistration_with_photos(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $ops = $this->opsUser();
        $package = $this->createPackage();
        $photo = app(PreregistrationPhotoService::class)
            ->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));

        $this->actingAs($ops)
            ->delete(route('preregistrations.destroy', $package->id));

        $this->actingAs($admin)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertDontSee('Eliminados');

        $this->actingAs($admin)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSee('Eliminados')
            ->assertSee('Preregistros para recuperar');

        $this->actingAs($admin)
            ->get(route('audit.trashed'))
            ->assertOk()
            ->assertSee($package->tracking_external)
            ->assertSee('Recuperar');

        $this->actingAs($admin)
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect(route('preregistrations.show', $package->id));

        $this->assertDatabaseHas('preregistrations', ['id' => $package->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('preregistration_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->path);

        $this->actingAs($ops)
            ->get(route('preregistrations.index'))
            ->assertOk()
            ->assertSee($package->tracking_external);
    }

    public function test_restore_is_blocked_when_tracking_is_already_in_use(): void
    {
        $admin = $this->adminUser();
        $package = $this->createPackage();
        $tracking = $package->tracking_external;
        $package->delete();

        $this->createPackage($tracking);

        $this->actingAs($admin)
            ->from(route('audit.trashed'))
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect(route('audit.trashed'))
            ->assertSessionHas('error');

        $this->assertSoftDeleted('preregistrations', ['id' => $package->id]);
    }

    public function test_expired_deleted_preregistrations_are_purged_with_photos(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $package = $this->createPackage();
        $photo = app(PreregistrationPhotoService::class)
            ->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));
        $package->delete();
        $package->deleted_at = now()->subDays(8);
        $package->save();

        $this->artisan('preregistrations:purge-deleted')
            ->assertSuccessful();

        $this->assertDatabaseMissing('preregistrations', ['id' => $package->id]);
        $this->assertDatabaseMissing('preregistration_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);

        $this->actingAs($admin)
            ->get(route('audit.trashed'))
            ->assertOk()
            ->assertDontSee($package->tracking_external);
    }

    public function test_admin_cannot_restore_after_the_retention_window(): void
    {
        $admin = $this->adminUser();
        $package = $this->createPackage();
        $package->delete();
        $package->deleted_at = now()->subDays(8);
        $package->save();

        $this->actingAs($admin)
            ->from(route('audit.trashed'))
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect(route('audit.trashed'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('preregistrations', ['id' => $package->id]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'agency_id' => null,
            'is_admin' => true,
        ]);
    }

    private function opsUser(): User
    {
        return User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => array_merge(Permission::operationalDefaults(), [
                Permission::ACTION_DELETE_PREREGISTRATION,
            ]),
        ]);
    }

    private function createPackage(?string $tracking = null): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => $tracking ?? ('TRK-DEL-'.random_int(1000, 9999)),
            'warehouse_code' => (string) random_int(770011, 779999),
            'label_name' => 'Paquete recuperable',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'PHOTO_PENDING',
        ]);
    }
}
