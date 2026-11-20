<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutoAirSackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function capture(User $user, string $tracking, string $service = 'AIR', ?float $weight = null): Preregistration
    {
        $payload = [
            'tracking_external' => $tracking,
            'service_type' => $service,
            'photos' => [UploadedFile::fake()->image('caja.jpg', 400, 400)],
        ];
        if ($weight !== null) {
            $payload['intake_weight_lbs'] = $weight;
        }

        $this->actingAs($user)
            ->post(route('preregistrations.store-quick-courier'), $payload)
            ->assertRedirect();

        return Preregistration::where('tracking_external', $tracking)->firstOrFail();
    }

    private function sackOf(Preregistration $package): ?Consolidation
    {
        return $package->fresh()->consolidationItem?->consolidation;
    }

    public function test_air_packages_share_one_auto_sack_created_on_first_capture(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $first = $this->capture($user, '1ZAUTOAIR001');
        $sack = $this->sackOf($first);

        $this->assertNotNull($sack);
        $this->assertTrue($sack->is_auto);
        $this->assertSame('AIR', $sack->service_type);
        $this->assertSame('OPEN', $sack->status);
        $this->assertMatchesRegularExpression('/^SAC-\d{6}-\d{4}$/', $sack->code);

        $second = $this->capture($user, '1ZAUTOAIR002');
        $this->assertSame($sack->id, $this->sackOf($second)?->id);
        $this->assertSame(1, Consolidation::count());
        $this->assertSame(2, $sack->items()->count());
    }

    public function test_sea_and_tbc_captures_do_not_enter_any_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $sea = $this->capture($user, '1ZAUTOSEA001', 'SEA', 5);
        $tbc = $this->capture($user, '1ZAUTOTBC001', 'TBC');

        $this->assertNull($this->sackOf($sea));
        $this->assertNull($this->sackOf($tbc));
        $this->assertSame(0, Consolidation::count());
    }

    public function test_air_capture_uses_existing_manual_open_air_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $manual = Consolidation::create([
            'code' => 'SAC-202611-0099',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);

        $package = $this->capture($user, '1ZAUTOMANUAL01');

        $this->assertSame($manual->id, $this->sackOf($package)?->id);
        $this->assertSame(1, Consolidation::count());
    }

    public function test_air_capture_uses_most_recent_open_air_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        Consolidation::create(['code' => 'SAC-202611-0090', 'service_type' => 'AIR', 'status' => 'OPEN']);
        $latest = Consolidation::create(['code' => 'SAC-202611-0091', 'service_type' => 'AIR', 'status' => 'OPEN']);
        Consolidation::create(['code' => 'CNT-202611-0092', 'service_type' => 'SEA', 'status' => 'OPEN']);

        $package = $this->capture($user, '1ZAUTOLATEST01');

        $this->assertSame($latest->id, $this->sackOf($package)?->id);
    }

    public function test_single_box_sack_never_receives_auto_packages(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $single = Consolidation::create([
            'code' => 'SAC-202611-0093',
            'service_type' => 'AIR',
            'status' => 'OPEN',
            'is_single' => true,
        ]);

        $package = $this->capture($user, '1ZAUTOSINGLE01');
        $sack = $this->sackOf($package);

        $this->assertNotNull($sack);
        $this->assertNotSame($single->id, $sack->id);
        $this->assertTrue($sack->is_auto);
        $this->assertSame(0, $single->items()->count());
    }

    public function test_single_box_sack_from_package_is_marked_single(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZSINGLEFLAG01',
            'warehouse_code' => '009001',
            'label_name' => 'CLIENTE',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.create-single-consolidation', $package->id))
            ->assertRedirect();

        $this->assertTrue((bool) $this->sackOf($package)?->is_single);
    }

    public function test_sending_auto_sack_makes_next_air_capture_open_a_new_one(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $first = $this->capture($user, '1ZAUTOSEND001');
        $sack = $this->sackOf($first);

        $this->actingAs($user)
            ->post(route('consolidations.send', $sack->id))
            ->assertRedirect();
        $this->assertSame('SENT', $sack->fresh()->status);

        $next = $this->capture($user, '1ZAUTOSEND002');
        $newSack = $this->sackOf($next);

        $this->assertNotNull($newSack);
        $this->assertNotSame($sack->id, $newSack->id);
        $this->assertSame('OPEN', $newSack->status);
        $this->assertTrue($newSack->is_auto);
    }

    public function test_changing_auto_sack_package_to_sea_removes_it_from_the_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $package = $this->capture($user, '1ZAUTOCHANGE01');
        $this->assertNotNull($this->sackOf($package));

        $this->actingAs($user)
            ->patch(route('preregistrations.service-type', $package->id), [
                'service_route' => 'SEA',
                'sea_billing' => 'LBS',
            ])
            ->assertRedirect()
            ->assertSessionMissing('error');

        $this->assertNull($this->sackOf($package));
        $this->assertSame('SEA', $package->fresh()->service_type);
    }

    public function test_completing_auto_sack_package_as_sea_removes_it_from_the_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Auto',
            'code' => 'AU01',
            'phone' => '555-0101',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = $this->capture($user, '1ZAUTOEDIT001');
        $this->assertNotNull($this->sackOf($package));

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'CLIENTE AUTO',
                'service_route' => 'SEA',
                'sea_billing' => 'LBS',
                'intake_weight_lbs' => 4,
                'tracking_external' => '1ZAUTOEDIT001',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($this->sackOf($package));
        $this->assertSame('SEA', $package->fresh()->service_type);
    }

    public function test_package_in_open_auto_sack_can_be_deleted(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $package = $this->capture($admin, '1ZAUTODELETE01');
        $sack = $this->sackOf($package);

        $this->actingAs($admin)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('preregistrations', ['id' => $package->id]);
        $this->assertSame(0, ConsolidationItem::where('consolidation_id', $sack->id)->count());
    }

    public function test_package_in_manual_sack_still_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $package = $this->capture($admin, '1ZAUTOMANDEL01', 'SEA', 3);
        $manual = Consolidation::create([
            'code' => 'CNT-202611-0099',
            'service_type' => 'SEA',
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $manual->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('preregistrations', ['id' => $package->id]);
    }

    public function test_sack_list_and_detail_mark_auto_sack(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $sack = $this->sackOf($this->capture($user, '1ZAUTOBADGE01'));

        $this->actingAs($user)
            ->get(route('consolidations.index', ['clear_filters' => 1]))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('consolidations.index'))
            ->assertOk()
            ->assertSee($sack->code)
            ->assertSee('Automático');

        $this->actingAs($user)
            ->get(route('consolidations.show', $sack->id))
            ->assertOk()
            ->assertSee('Automático');
    }
}
