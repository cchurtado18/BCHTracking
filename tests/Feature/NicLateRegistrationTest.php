<?php

namespace Tests\Feature;

use App\Mail\PackageReceivedInMiami;
use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NicLateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
    }

    private function user(): User
    {
        return User::factory()->create(['agency_id' => null, 'is_admin' => true]);
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Tardía',
            'code' => 'AT01',
            'phone' => '555-0199',
            'email' => 'tardia@example.com',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function sack(string $status = 'SENT', string $service = 'AIR', string $code = 'SAC-202611-0100'): Consolidation
    {
        return Consolidation::create([
            'code' => $code,
            'service_type' => $service,
            'status' => $status,
            'sent_at' => $status === 'SENT' ? now() : null,
        ]);
    }

    private function payload(Consolidation $sack, Agency $agency, array $overrides = []): array
    {
        return array_merge([
            'arrived_consolidation_id' => $sack->id,
            'intake_type' => 'COURIER',
            'agency_id' => $agency->id,
            'tracking_external' => '1ZLATE0000001',
            'label_name' => 'Cliente Tardío',
            'intake_weight_lbs' => 6.5,
            'service_route' => 'AIR',
            'photo' => UploadedFile::fake()->image('caja.jpg', 300, 300),
        ], $overrides);
    }

    public function test_package_is_stored_inside_sent_sack_as_received_in_nicaragua(): void
    {
        $sack = $this->sack();
        $openSack = $this->sack('OPEN', 'AIR', 'SAC-202611-0101');

        $response = $this->actingAs($this->user())
            ->post(route('preregistrations.store'), $this->payload($sack, $this->agency()));

        $package = Preregistration::where('tracking_external', '1ZLATE0000001')->firstOrFail();
        $response->assertRedirect(route('nic-consolidations.arrived-label', $package->id));

        $this->assertSame('IN_WAREHOUSE_NIC', $package->status);
        $this->assertTrue($package->registered_in_nic);
        $this->assertNotNull($package->received_nic_at);
        $this->assertNotEmpty($package->warehouse_code);
        $this->assertSame(1, $package->photos()->count());

        $item = $package->consolidationItem;
        $this->assertSame($sack->id, (int) $item->consolidation_id);
        $this->assertNotNull($item->scanned_at);
        $this->assertSame(0, $openSack->items()->count());

        Mail::assertNotSent(PackageReceivedInMiami::class);
    }

    public function test_cubic_feet_package_is_stored_in_sent_container(): void
    {
        $container = $this->sack('SENT', 'SEA', 'CON-202611-0001');

        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), $this->payload($container, $this->agency(), [
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '20 x 20 x 20 in',
            ]))
            ->assertSessionHasNoErrors();

        $package = Preregistration::where('tracking_external', '1ZLATE0000001')->firstOrFail();
        $this->assertSame('CFT', $package->service_type);
        $this->assertSame($container->id, (int) $package->consolidationItem->consolidation_id);
    }

    public function test_all_fields_are_required(): void
    {
        $sack = $this->sack();

        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), [
                'arrived_consolidation_id' => $sack->id,
                'service_route' => 'AIR',
            ])
            ->assertSessionHasErrors(['agency_id', 'tracking_external', 'label_name', 'intake_weight_lbs', 'photo']);

        $this->assertSame(0, Preregistration::count());
    }

    public function test_cubic_feet_requires_dimension(): void
    {
        $container = $this->sack('SENT', 'SEA', 'CON-202611-0002');

        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), $this->payload($container, $this->agency(), [
                'service_route' => 'SEA',
                'sea_billing' => 'CFT',
                'dimension' => '',
            ]))
            ->assertSessionHasErrors('dimension');
    }

    public function test_rejects_open_sack_and_service_mismatch(): void
    {
        $user = $this->user();
        $agency = $this->agency();

        $this->actingAs($user)
            ->post(route('preregistrations.store'), $this->payload($this->sack('OPEN'), $agency))
            ->assertSessionHasErrors('arrived_consolidation_id');

        $this->actingAs($user)
            ->post(route('preregistrations.store'), $this->payload($this->sack('SENT', 'SEA', 'CON-202611-0003'), $agency, [
                'tracking_external' => '1ZLATE0000002',
            ]))
            ->assertSessionHasErrors('service_type');

        $this->assertSame(0, Preregistration::count());
    }

    public function test_drop_off_is_not_allowed_in_this_mode(): void
    {
        $sack = $this->sack();

        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), $this->payload($sack, $this->agency(), [
                'intake_type' => 'DROP_OFF',
                'bultos_count' => 2,
                'bultos' => [['label_name' => 'A'], ['label_name' => 'B']],
            ]))
            ->assertSessionHasErrors('bultos');
    }

    public function test_code_scanned_in_sack_without_preregistration_is_linked_not_duplicated(): void
    {
        $sack = $this->sack();
        $unmatched = ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'unmatched_code' => '1ZLATE0000001',
        ]);

        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), $this->payload($sack, $this->agency()))
            ->assertSessionHasNoErrors();

        $package = Preregistration::where('tracking_external', '1ZLATE0000001')->firstOrFail();
        $this->assertSame(1, $sack->items()->count());
        $this->assertSame($package->id, (int) $unmatched->fresh()->preregistration_id);
        $this->assertNotNull($unmatched->fresh()->scanned_at);
        $this->assertSame('IN_WAREHOUSE_NIC', $package->fresh()->status);
    }

    public function test_normal_preregistration_is_unchanged(): void
    {
        $this->actingAs($this->user())
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $this->agency()->id,
                'tracking_external' => '1ZNORMAL00001',
                'label_name' => 'Cliente Normal',
                'intake_weight_lbs' => 3,
                'service_route' => 'AIR',
                'photo' => UploadedFile::fake()->image('caja.jpg', 300, 300),
            ])
            ->assertSessionHasNoErrors();

        $package = Preregistration::where('tracking_external', '1ZNORMAL00001')->firstOrFail();
        $this->assertSame('RECEIVED_MIAMI', $package->status);
        $this->assertFalse($package->registered_in_nic);
    }

    public function test_nic_scan_of_unknown_code_offers_register_link(): void
    {
        $sack = $this->sack();

        $this->actingAs($this->user())
            ->postJson(route('nic-consolidations.scan', $sack->id), ['code' => '1ZUNKNOWN0001'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Código no encontrado.')
            ->assertJsonPath('register_url', route('nic-consolidations.register-arrived', [
                'id' => $sack->id,
                'tracking' => '1ZUNKNOWN0001',
            ]));
    }

    public function test_nic_only_user_can_register_arrived_package_and_print_label(): void
    {
        $sack = $this->sack();
        $user = User::factory()->create(['agency_id' => null, 'permissions' => ['module.nic']]);

        $this->actingAs($user)
            ->postJson(route('nic-consolidations.scan', $sack->id), ['code' => '1ZUNKNOWN0001'])
            ->assertStatus(422)
            ->assertJsonPath('register_url', route('nic-consolidations.register-arrived', [
                'id' => $sack->id,
                'tracking' => '1ZUNKNOWN0001',
            ]));

        $this->actingAs($user)
            ->get(route('nic-consolidations.register-arrived', ['id' => $sack->id, 'tracking' => '1ZUNKNOWN0001']))
            ->assertOk()
            ->assertSee('action="'.route('nic-consolidations.store-arrived').'"', false)
            ->assertSee('value="1ZUNKNOWN0001"', false);

        $response = $this->actingAs($user)
            ->post(route('nic-consolidations.store-arrived'), $this->payload($sack, $this->agency(), [
                'tracking_external' => '1ZUNKNOWN0001',
            ]));

        $package = Preregistration::where('tracking_external', '1ZUNKNOWN0001')->firstOrFail();
        $response->assertRedirect(route('nic-consolidations.arrived-label', $package->id));
        $this->assertSame('IN_WAREHOUSE_NIC', $package->status);
        $this->assertSame($sack->id, (int) $package->consolidationItem->consolidation_id);

        $this->actingAs($user)
            ->get(route('nic-consolidations.arrived-label', $package->id))
            ->assertOk()
            ->assertSee($package->warehouse_code)
            ->assertSee(route('nic-consolidations.show', $sack->id), false);
    }

    public function test_nic_only_user_cannot_create_normal_preregistrations(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'permissions' => ['module.nic']]);
        $agency = $this->agency();

        $this->actingAs($user)
            ->post(route('nic-consolidations.store-arrived'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'tracking_external' => '1ZNORMAL00002',
                'label_name' => 'Normal',
                'intake_weight_lbs' => 3,
                'service_route' => 'AIR',
                'photo' => UploadedFile::fake()->image('caja.jpg', 300, 300),
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertRedirect();

        $normal = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZMIAMI000002',
            'warehouse_code' => '009002',
            'label_name' => 'MIAMI',
            'service_type' => 'AIR',
            'status' => 'RECEIVED_MIAMI',
        ]);
        $this->actingAs($user)
            ->get(route('nic-consolidations.arrived-label', $normal->id))
            ->assertNotFound();

        $this->assertSame(1, Preregistration::count());
    }

    public function test_create_form_shows_sack_and_locks_service(): void
    {
        $sack = $this->sack();

        $this->actingAs($this->user())
            ->get(route('preregistrations.create', ['arrived_consolidation_id' => $sack->id, 'tracking' => '1zlate0000009']))
            ->assertOk()
            ->assertSee('SAC-202611-0100')
            ->assertSee('name="arrived_consolidation_id"', false)
            ->assertSee('value="1ZLATE0000009"', false)
            ->assertDontSee('<option value="DROP_OFF">', false)
            ->assertDontSee('TBC — Por confirmar');
    }

    public function test_create_form_rejects_sack_not_sent(): void
    {
        $sack = $this->sack('OPEN');

        $this->actingAs($this->user())
            ->get(route('preregistrations.create', ['arrived_consolidation_id' => $sack->id]))
            ->assertRedirect(route('nic-consolidations.index'));
    }

    public function test_registered_in_nic_filter_and_badge(): void
    {
        $sack = $this->sack();
        $user = $this->user();
        $this->actingAs($user)
            ->post(route('preregistrations.store'), $this->payload($sack, $this->agency()));
        Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => '1ZMIAMI000001',
            'warehouse_code' => '009001',
            'label_name' => 'OTRO CLIENTE',
            'service_type' => 'AIR',
            'status' => 'RECEIVED_MIAMI',
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.index', ['consolidation' => 'registered_nic']))
            ->assertOk()
            ->assertSee('CLIENTE TARDÍO')
            ->assertSee('Registrado en Nicaragua')
            ->assertDontSee('OTRO CLIENTE');
    }
}
