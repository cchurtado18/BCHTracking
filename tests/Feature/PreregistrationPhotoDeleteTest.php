<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\PreregistrationPhoto;
use App\Models\User;
use App\Services\PreregistrationPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreregistrationPhotoDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_user_can_delete_a_saved_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage();
        $keep = $this->uploadPhoto($package, 'keep.jpg', 240, 240);
        $remove = $this->uploadPhoto($package, 'remove.jpg', 320, 180);

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->delete(route('preregistrations.photos.destroy', [
                'id' => $package->id,
                'photo' => $remove->id,
            ]))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('preregistration_photos', ['id' => $remove->id]);
        $this->assertDatabaseHas('preregistration_photos', ['id' => $keep->id]);
        Storage::disk('public')->assertMissing($remove->path);
        Storage::disk('public')->assertExists($keep->path);
    }

    public function test_preregistration_and_package_views_show_delete_overlay_when_complete_package_has_extra_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage();
        $this->uploadPhoto($package, 'caja-1.jpg', 240, 240);
        $second = $this->uploadPhoto($package, 'caja-2.jpg', 320, 180);
        $deleteUrl = route('preregistrations.photos.destroy', [
            'id' => $package->id,
            'photo' => $second->id,
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertSee('aria-label="Eliminar foto"', false)
            ->assertSee($deleteUrl, false);

        $this->actingAs($user)
            ->get(route('preregistrations.edit', $package->id))
            ->assertOk()
            ->assertSee('aria-label="Eliminar foto"', false);

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('aria-label="Eliminar foto"', false);
    }

    public function test_complete_package_keeps_the_last_photo_as_backup(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage();
        $photo = $this->uploadPhoto($package, 'unica.jpg');

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->delete(route('preregistrations.photos.destroy', [
                'id' => $package->id,
                'photo' => $photo->id,
            ]))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('preregistration_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->path);

        $this->actingAs($user)
            ->get(route('preregistrations.show', $package->id))
            ->assertOk()
            ->assertDontSee('aria-label="Eliminar foto"', false);

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertDontSee('aria-label="Eliminar foto"', false);
    }

    public function test_pending_package_can_delete_its_last_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('PHOTO_PENDING');
        $photo = $this->uploadPhoto($package, 'pendiente.jpg');

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->delete(route('preregistrations.photos.destroy', [
                'id' => $package->id,
                'photo' => $photo->id,
            ]))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('preregistration_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_agency_user_cannot_delete_a_photo(): void
    {
        Storage::fake('public');
        $agency = Agency::create([
            'name' => 'Agencia Foto',
            'code' => 'AF'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = $this->createPackage();
        $package->update(['agency_id' => $agency->id]);
        $photo = $this->uploadPhoto($package, 'caja.jpg');
        $user = User::factory()->create(['agency_id' => $agency->id]);

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertDontSee('aria-label="Eliminar foto"', false);

        $this->actingAs($user)
            ->delete(route('preregistrations.photos.destroy', [
                'id' => $package->id,
                'photo' => $photo->id,
            ]))
            ->assertRedirect(route('packages.index'));

        $this->assertDatabaseHas('preregistration_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_cannot_delete_photo_from_another_package(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage();
        $other = $this->createPackage();
        $photo = $this->uploadPhoto($other, 'otra.jpg');

        $this->actingAs($user)
            ->delete(route('preregistrations.photos.destroy', [
                'id' => $package->id,
                'photo' => $photo->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('preregistration_photos', ['id' => $photo->id]);
    }

    private function uploadPhoto(Preregistration $package, string $name, int $width = 240, int $height = 240): PreregistrationPhoto
    {
        return app(PreregistrationPhotoService::class)
            ->uploadPhoto($package, UploadedFile::fake()->image($name, $width, $height));
    }

    private function createPackage(string $status = 'RECEIVED_MIAMI'): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRKDEL'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(870011, 879999),
            'label_name' => 'Foto Paquete',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => $status,
        ]);
    }
}
