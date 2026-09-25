<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsolidationScanLookupTest extends TestCase
{
    use RefreshDatabase;

    private function centralUser(): User
    {
        return User::factory()->create(['agency_id' => null]);
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Scan Speed',
            'code' => 'SC'.random_int(10, 99),
            'phone' => '2222-5050',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function package(Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'SPXMIA010062601540002015',
            'warehouse_code' => '010015',
            'label_name' => 'Cliente Scan',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4.5,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ], $overrides));
    }

    public function test_create_scan_page_adds_codes_before_lookup_returns(): void
    {
        $user = $this->centralUser();

        $html = $this->actingAs($user)
            ->get(route('consolidations.create-scan'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Agregado: sin preregistro', $html);
        $this->assertStringContainsString('lines.push(entry)', $html);
        $this->assertStringNotContainsString("setFeedback('Buscando '", $html);
    }

    public function test_create_scan_page_does_not_embed_all_miami_packages(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency);

        $this->actingAs($user)
            ->get(route('consolidations.create-scan'))
            ->assertOk()
            ->assertDontSee('id="scan-lookup-json"', false)
            ->assertSee('data-lookup-url', false)
            ->assertSee(route('consolidations.scan-lookup'), false);
    }

    public function test_scan_lookup_returns_available_package_by_tracking(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => 'spxmia010062601540002015',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'service_mismatch' => false,
                'package' => [
                    'tracking' => 'SPXMIA010062601540002015',
                    'warehouse' => '010015',
                    'label' => 'CLIENTE SCAN',
                    'service_type' => 'AIR',
                ],
            ]);
    }

    public function test_scan_lookup_reports_service_mismatch(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency, ['service_type' => 'SEA', 'tracking_external' => 'SEATRK99999999']);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => 'SEATRK99999999',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => false,
                'service_mismatch' => true,
            ]);
    }

    public function test_open_sack_scan_mode_does_not_embed_all_miami_packages(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency, ['tracking_external' => 'OTHERTRK12345678', 'warehouse_code' => '010099']);

        $sack = Consolidation::create([
            'code' => 'SAC-SCAN-1001',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->get(route('consolidations.show', ['consolidation' => $sack->id, 'mode' => 'scan']))
            ->assertOk()
            ->assertDontSee('id="cons-show-scan-lookup"', false)
            ->assertDontSee('OTHERTRK12345678')
            ->assertSee('id="cons-show-scan-meta"', false);
    }

    public function test_scan_item_still_matches_tracking_on_open_sack(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $package = $this->package($agency);
        $sack = Consolidation::create([
            'code' => 'SAC-SCAN-1002',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->post(route('consolidations.scan-item', $sack->id), [
                'entry_code' => 'SPXMIA010062601540002015',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consolidation_items', [
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);
    }

    public function test_scan_lookup_unknown_tracking_returns_not_found(): void
    {
        $user = $this->centralUser();

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => 'UNKNOWNTRACKING999',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => false,
                'service_mismatch' => false,
                'package' => null,
            ]);
    }

    public function test_scan_lookup_returns_photo_pending_package_weight(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency, [
            'tracking_external' => 'TRK-PENDING-WEIGHT',
            'warehouse_code' => null,
            'label_name' => '[PENDIENTE]',
            'intake_weight_lbs' => 7.25,
            'status' => 'PHOTO_PENDING',
            'agency_id' => null,
        ]);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => 'TRK-PENDING-WEIGHT',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'service_mismatch' => false,
                'package' => [
                    'tracking' => 'TRK-PENDING-WEIGHT',
                    'weight_lbs' => 7.25,
                    'incomplete' => true,
                ],
            ]);
    }

    public function test_scan_create_includes_photo_pending_weight_in_sack_total(): void
    {
        $user = $this->centralUser();
        $this->package($this->agency(), [
            'tracking_external' => 'TRK-PENDING-SACK',
            'warehouse_code' => null,
            'label_name' => '[PENDIENTE]',
            'intake_weight_lbs' => 5.5,
            'status' => 'PHOTO_PENDING',
            'agency_id' => null,
        ]);

        $this->actingAs($user)
            ->post(route('consolidations.store-scan'), [
                'service_type' => 'AIR',
                'entry_codes' => ['TRK-PENDING-SACK'],
            ])
            ->assertRedirect();

        $item = ConsolidationItem::query()->whereHas('preregistration', function ($q) {
            $q->where('tracking_external', 'TRK-PENDING-SACK');
        })->first();
        $this->assertNotNull($item);
        $this->assertSame('PHOTO_PENDING', $item->preregistration->status);
        $this->assertEquals(5.5, (float) app(\App\Services\ConsolidationService::class)
            ->getReport($item->consolidation->fresh('items.preregistration'))['total_lbs']);
    }

    public function test_open_sack_scan_accepts_photo_pending_and_keeps_fields_pending(): void
    {
        $user = $this->centralUser();
        $package = $this->package($this->agency(), [
            'tracking_external' => 'TRK-PENDING-OPEN',
            'warehouse_code' => null,
            'label_name' => '[PENDIENTE]',
            'intake_weight_lbs' => 3.75,
            'status' => 'PHOTO_PENDING',
            'agency_id' => null,
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-SCAN-PEND',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->post(route('consolidations.scan-item', $sack->id), [
                'entry_code' => 'TRK-PENDING-OPEN',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consolidation_items', [
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);
        $this->assertSame('PHOTO_PENDING', $package->fresh()->status);
        $this->assertSame('[PENDIENTE]', $package->fresh()->label_name);
        $this->assertEquals(3.75, (float) app(\App\Services\ConsolidationService::class)
            ->getReport($sack->fresh('items.preregistration'))['total_lbs']);

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Datos pendientes')
            ->assertSee('3.75');
    }

    public function test_packages_already_in_a_sack_are_not_returned_by_lookup(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $package = $this->package($agency, ['tracking_external' => 'TAKEN12345678']);
        $sack = Consolidation::create([
            'code' => 'SAC-SCAN-1003',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => 'TAKEN12345678',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => false,
                'service_mismatch' => false,
                'package' => null,
            ]);
    }

    public function test_saving_usps_barcode_keeps_only_the_tracking_core(): void
    {
        $agency = $this->agency();
        $package = $this->package($agency, [
            'tracking_external' => '42033142940011189956253786216799',
            'warehouse_code' => '010077',
        ]);

        $this->assertSame('9400111899562537862167', $package->tracking_external);
        $this->assertDatabaseHas('preregistrations', [
            'id' => $package->id,
            'tracking_external' => '9400111899562537862167',
        ]);
    }

    public function test_scan_lookup_finds_clean_usps_from_prefixed_barcode(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $this->package($agency, [
            'tracking_external' => '9400111899562537862167',
            'warehouse_code' => '010078',
        ]);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => '42033142940011189956253786216799',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'package' => [
                    'tracking' => '9400111899562537862167',
                    'warehouse' => '010078',
                ],
            ]);
    }

    public function test_scan_lookup_finds_legacy_prefixed_usps_from_clean_scan(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $package = $this->package($agency, [
            'tracking_external' => '9400111899562537862167',
            'warehouse_code' => '010079',
        ]);
        DB::table('preregistrations')->where('id', $package->id)->update([
            'tracking_external' => '420331429400111899562537862167',
        ]);

        $this->actingAs($user)
            ->getJson(route('consolidations.scan-lookup', [
                'code' => '9400111899562537862167',
                'service_type' => 'AIR',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'package' => [
                    'warehouse' => '010079',
                ],
            ]);
    }

    public function test_open_sack_scan_accepts_usps_prefix_and_extra_digits(): void
    {
        $user = $this->centralUser();
        $agency = $this->agency();
        $package = $this->package($agency, [
            'tracking_external' => '9400111899562537862167',
            'warehouse_code' => '010080',
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-SCAN-USPS',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->post(route('consolidations.scan-item', $sack->id), [
                'entry_code' => '42033142940011189956253786216799',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consolidation_items', [
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);
    }
}
