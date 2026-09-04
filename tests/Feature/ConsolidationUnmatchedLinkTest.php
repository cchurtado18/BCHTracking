<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use App\Services\ConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidationUnmatchedLinkTest extends TestCase
{
    use RefreshDatabase;

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Saco Link',
            'code' => 'SL'.random_int(10, 99),
            'phone' => '2222-4040',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function openSackWithUnmatched(string $code, string $service = 'AIR'): Consolidation
    {
        $sack = Consolidation::create([
            'code' => 'SAC-LINK-'.random_int(1000, 9999),
            'service_type' => $service,
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => null,
            'unmatched_code' => strtoupper($code),
        ]);

        return $sack;
    }

    public function test_creating_preregistration_links_unmatched_sack_item_and_updates_weight(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $sack = $this->openSackWithUnmatched('trk-late-001');

        $this->assertSame(0.0, (float) app(ConsolidationService::class)->getReport($sack->fresh('items.preregistration'))['total_lbs']);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Cliente tarde',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 8.5,
                'tracking_external' => 'TRK-LATE-001',
            ])
            ->assertRedirect();

        $package = Preregistration::where('tracking_external', 'TRK-LATE-001')->first();
        $this->assertNotNull($package);

        $item = ConsolidationItem::where('consolidation_id', $sack->id)->first();
        $this->assertSame($package->id, (int) $item->preregistration_id);

        $report = app(ConsolidationService::class)->getReport($sack->fresh('items.preregistration'));
        $this->assertEquals(8.5, (float) $report['total_lbs']);
        $this->assertSame(0, $report['unmatched_count']);

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Cliente tarde')
            ->assertDontSee('Solo código guardado en el saco');

        $this->actingAs($user)
            ->get(route('consolidations.report', $sack->id))
            ->assertOk()
            ->assertSee('TRK-LATE-001')
            ->assertSee('8.50');
    }

    public function test_completing_photo_pending_links_unmatched_sack_item(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();
        $sack = $this->openSackWithUnmatched('TRK-PHOTO-SACK');

        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-PHOTO-SACK',
            'label_name' => '[PENDIENTE]',
            'status' => 'PHOTO_PENDING',
        ]);

        $this->assertNull(ConsolidationItem::where('consolidation_id', $sack->id)->value('preregistration_id'));

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'Cliente foto',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 6,
                'tracking_external' => 'TRK-PHOTO-SACK',
            ])
            ->assertRedirect();

        $item = ConsolidationItem::where('consolidation_id', $sack->id)->first();
        $this->assertSame($package->id, (int) $item->preregistration_id);
        $this->assertEquals(6.0, (float) app(ConsolidationService::class)->getReport($sack->fresh('items.preregistration'))['total_lbs']);
    }

    public function test_does_not_link_when_service_type_does_not_match_sack(): void
    {
        $agency = $this->agency();
        $sack = $this->openSackWithUnmatched('TRK-SEA-MISMATCH', 'SEA');

        Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-SEA-MISMATCH',
            'label_name' => 'Aéreo en saco marítimo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $item = ConsolidationItem::where('consolidation_id', $sack->id)->first();
        $this->assertNull($item->preregistration_id);
        $this->assertSame('TRK-SEA-MISMATCH', $item->unmatched_code);
    }

    public function test_linking_into_sent_sack_marks_package_in_transit(): void
    {
        $agency = $this->agency();
        $sack = $this->openSackWithUnmatched('TRK-SENT-LINK');
        $sack->update(['status' => 'SENT', 'sent_at' => now()]);

        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-SENT-LINK',
            'label_name' => 'Ya viajó',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $this->assertSame($package->id, (int) ConsolidationItem::where('consolidation_id', $sack->id)->value('preregistration_id'));
        $this->assertSame('IN_TRANSIT', $package->fresh()->status);
    }

    public function test_opening_sack_links_existing_preregistration_to_unmatched_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();

        Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TBA334243264207',
            'label_name' => 'Cliente ya existia',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4.25,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $sack = $this->openSackWithUnmatched('TBA334243264207');
        $item = ConsolidationItem::where('consolidation_id', $sack->id)->first();
        $this->assertNull($item->preregistration_id);

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Cliente ya existia')
            ->assertSee('4.25')
            ->assertDontSee('Solo código guardado en el saco');

        $this->assertNotNull($item->fresh()->preregistration_id);
    }

    public function test_opening_sack_links_when_tracking_has_spaces(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();

        Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TBA 334300892182',
            'label_name' => 'Tracking con espacio',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);

        $sack = $this->openSackWithUnmatched('TBA334300892182');

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Tracking con espacio');

        $this->assertNotNull(ConsolidationItem::where('consolidation_id', $sack->id)->value('preregistration_id'));
    }

    public function test_opening_sack_drops_unmatched_duplicate_when_package_already_in_same_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->agency();

        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TBA334295077748',
            'label_name' => 'Ya estaba en el saco',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
        $sack = $this->openSackWithUnmatched('TBA334295077748');
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Ya estaba en el saco')
            ->assertDontSee('Solo código guardado en el saco');

        $this->assertSame(1, ConsolidationItem::where('consolidation_id', $sack->id)->count());
    }
}
