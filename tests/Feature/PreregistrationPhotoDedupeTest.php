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

    public function test_pending_preregistration_can_be_deleted_with_its_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('PHOTO_PENDING');
        $service = app(PreregistrationPhotoService::class);
        $first = $service->uploadPhoto($package, UploadedFile::fake()->image('caja-1.jpg', 240, 240));
        $second = $service->uploadPhoto($package, UploadedFile::fake()->image('caja-2.jpg', 240, 240));

        $this->actingAs($user)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect(route('preregistrations.index'));

        $this->assertSoftDeleted('preregistrations', ['id' => $package->id]);
        $this->assertDatabaseMissing('preregistration_photos', ['preregistration_id' => $package->id]);
        Storage::disk('public')->assertMissing($first->path);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_in_process_preregistration_cannot_be_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->createPackage('IN_TRANSIT');
        $service = app(PreregistrationPhotoService::class);
        $photo = $service->uploadPhoto($package, UploadedFile::fake()->image('caja.jpg', 240, 240));

        $this->actingAs($user)
            ->from(route('preregistrations.index'))
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect(route('preregistrations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('preregistrations', ['id' => $package->id]);
        $this->assertSame(1, $package->photos()->count());
        Storage::disk('public')->assertExists($photo->path);
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
