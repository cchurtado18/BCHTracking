<?php

namespace Tests\Feature;

use App\Models\Preregistration;
use App\Models\PreregistrationPhoto;
use App\Models\User;
use App\Services\PreregistrationPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreregistrationPhotoDedupeTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_photo_upload_is_ignored(): void
    {
        Storage::fake('public');
        $package = $this->createPackage();
        $service = app(PreregistrationPhotoService::class);

        $first = $service->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));
        $copy = new UploadedFile(
            Storage::disk('public')->path($first->path),
            'caja-copy.jpg',
            'image/jpeg',
            null,
            true
        );
        $second = $service->uploadPhoto($package, $copy);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $package->photos()->count());
    }

    public function test_admin_can_delete_photo_on_pending_package(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('PHOTO_PENDING');
        $service = app(PreregistrationPhotoService::class);
        $photo = $service->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));

        $this->actingAs($user)
            ->delete(route('preregistrations.photos.destroy', ['id' => $package->id, 'photo' => $photo->id]))
            ->assertRedirect();

        $this->assertSame(0, $package->photos()->count());
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_cannot_delete_photo_unless_pending_completion(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('RECEIVED_MIAMI');
        $service = app(PreregistrationPhotoService::class);
        $photo = $service->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->delete(route('preregistrations.photos.destroy', ['id' => $package->id, 'photo' => $photo->id]))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('error');

        $this->assertSame(1, $package->photos()->count());
    }

    public function test_dedupe_command_only_cleans_pending_packages(): void
    {
        Storage::fake('public');
        $service = app(PreregistrationPhotoService::class);

        $pending = $this->createPackage('PHOTO_PENDING');
        $complete = $this->createPackage('RECEIVED_MIAMI');
        $this->addDuplicatePair($service, $pending);
        $this->addDuplicatePair($service, $complete);

        $this->artisan('preregistrations:dedupe-photos')
            ->assertSuccessful();

        $this->assertSame(1, $pending->photos()->count());
        $this->assertSame(2, $complete->photos()->count());
    }

    private function addDuplicatePair(PreregistrationPhotoService $service, Preregistration $package): void
    {
        $first = $service->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));
        $copyPath = 'preregistrations/dup-'.$package->id.'.jpg';
        Storage::disk('public')->put($copyPath, Storage::disk('public')->get($first->path));
        PreregistrationPhoto::create([
            'preregistration_id' => $package->id,
            'path' => $copyPath,
            'mime' => $first->mime,
            'size_bytes' => $first->size_bytes,
            'sort_order' => 1,
        ]);
    }

    private function createPackage(string $status = 'RECEIVED_MIAMI'): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRKPHOTO'.random_int(1000, 9999),
            'warehouse_code' => (string) random_int(880011, 889999),
            'label_name' => 'Foto Paquete',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => $status,
        ]);
    }
}
