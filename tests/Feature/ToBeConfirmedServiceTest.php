<?php

namespace Tests\Feature;

use App\Mail\PackageReceivedInMiami;
use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ToBeConfirmedServiceTest extends TestCase
{
    use RefreshDatabase;

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia TBC',
            'code' => 'TB'.random_int(10, 99),
            'phone' => '2222-3333',
            'is_active' => true,
            'is_main' => false,
            'billing_email' => 'tbc@cliente.test',
        ]);
    }

    public function test_quick_courier_can_save_tbc_without_warehouse(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZTBCQUICK001',
                'service_type' => 'TBC',
                'intake_weight_lbs' => 6.5,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        $package = Preregistration::where('tracking_external', '1ZTBCQUICK001')->first();
        $this->assertNotNull($package);
        $this->assertSame('TBC', $package->service_type);
        $this->assertSame('PHOTO_PENDING', $package->status);
        $this->assertNull($package->warehouse_code);
    }

    public function test_prealert_air_does_not_overwrite_tbc_on_quick_capture(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        Prealert::create([
            'name' => 'Cliente Prealerta TBC',
            'agency_id' => $agency->id,
            'tracking' => '1ZTBCPREALERT1',
            'service_type' => 'AIR',
            'status' => Prealert::STATUS_PENDING,
        ]);
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZTBCPREALERT1',
                'service_type' => 'TBC',
                'intake_weight_lbs' => 4,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        $package = Preregistration::where('tracking_external', '1ZTBCPREALERT1')->first();
        $this->assertNotNull($package);
        $this->assertSame('TBC', $package->service_type);
        $this->assertSame('PHOTO_PENDING', $package->status);
    }

    public function test_completing_tbc_quick_capture_stays_received_miami_and_emails_client(): void
    {
        Mail::fake();
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $photo = UploadedFile::fake()->image('caja.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), [
                'tracking_external' => '1ZTBCEMAIL001',
                'service_type' => 'TBC',
                'intake_weight_lbs' => 5,
                'photos' => [$photo],
            ])
            ->assertRedirect();

        Mail::assertNothingSent();

        $package = Preregistration::where('tracking_external', '1ZTBCEMAIL001')->first();
        $this->assertNotNull($package);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'Cliente TBC',
                'service_type' => 'TBC',
                'service_route' => 'TBC',
                'intake_weight_lbs' => 5,
                'tracking_external' => '1ZTBCEMAIL001',
            ])
            ->assertRedirect();

        $package->refresh();
        $this->assertSame('RECEIVED_MIAMI', $package->status);
        $this->assertSame('TBC', $package->service_type);
        Mail::assertSent(PackageReceivedInMiami::class);
        $this->assertNotNull($package->miami_received_notified_at);
    }

    public function test_tbc_package_cannot_create_a_single_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZTBCSINGLE001',
            'warehouse_code' => '667722',
            'label_name' => 'Cliente TBC Solo',
            'service_type' => 'TBC',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->from(route('preregistrations.show', $package->id))
            ->post(route('preregistrations.create-single-consolidation', $package->id))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('consolidation_items', [
            'preregistration_id' => $package->id,
        ]);
        $this->assertSame('TBC', $package->fresh()->service_type);
        $this->assertSame('RECEIVED_MIAMI', $package->fresh()->status);
    }

    public function test_tbc_package_cannot_enter_an_open_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZTBCSACK001',
            'warehouse_code' => '667788',
            'label_name' => 'Cliente TBC Saco',
            'service_type' => 'TBC',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-TBC-1',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->from(route('consolidations.show', $sack->id))
            ->post(route('consolidations.add-item', $sack->id), [
                'preregistration_id' => $package->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('consolidations.scan-item', $sack->id), [
                'entry_code' => '1ZTBCSACK001',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('consolidation_items', [
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);
    }

    public function test_confirming_tbc_to_air_allows_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZTBCOK001',
            'warehouse_code' => '667799',
            'label_name' => 'Cliente TBC OK',
            'service_type' => 'TBC',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-TBC-2',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->patch(route('preregistrations.service-type', $package->id), [
                'service_route' => 'AIR',
                'service_type' => 'AIR',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id));

        $package->refresh();
        $this->assertSame('AIR', $package->service_type);
        $this->assertSame('RECEIVED_MIAMI', $package->status);

        $this->actingAs($user)
            ->post(route('consolidations.add-item', $sack->id), [
                'preregistration_id' => $package->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(
            ConsolidationItem::where('consolidation_id', $sack->id)
                ->where('preregistration_id', $package->id)
                ->exists()
        );
    }

    public function test_tbc_label_prints_tbc_not_air(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZTBCLABEL001',
            'warehouse_code' => '667711',
            'label_name' => 'Cliente TBC Label',
            'service_type' => 'TBC',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $html = $this->actingAs($user)
            ->get(route('preregistrations.label', $package->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>TBC<', $html);
        $this->assertStringContainsString('sl-service-badge service-tbc', $html);
        $this->assertStringContainsString('sl-service-mark-large is-tbc', $html);
        $this->assertDoesNotMatchRegularExpression('/sl-service-badge service-air/', $html);
        $this->assertDoesNotMatchRegularExpression('/>AIR</', $html);
    }

    public function test_scan_lookup_treats_tbc_as_mismatch(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZTBLOOKUP001',
            'warehouse_code' => '667700',
            'label_name' => 'Cliente Lookup',
            'service_type' => 'TBC',
            'intake_weight_lbs' => 3,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => '1ZTBLOOKUP001',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => false,
                'service_mismatch' => true,
            ]);
    }
}
