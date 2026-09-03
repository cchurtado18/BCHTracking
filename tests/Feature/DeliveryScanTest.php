<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryScanTest extends TestCase
{
    use RefreshDatabase;

    private function createAgency(): Agency
    {
        $suffix = (string) random_int(1000, 9999);

        return Agency::create([
            'name' => 'Agencia Entrega '.$suffix,
            'code' => 'E'.$suffix,
            'phone' => '555-0100',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function createReadyPackage(Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => '1Z999AA10123456784',
            'warehouse_code' => '445566',
            'label_name' => 'Cliente Entrega',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 8,
            'status' => 'READY',
            'agency_id' => $agency->id,
            'ready_at' => now(),
        ], $overrides));
    }

    public function test_can_deliver_package_by_warehouse_code(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency);
        $note = DeliveryNote::create([
            'code' => 'SLO-9001',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->post(route('salidas.process-scan'), [
                'code' => '445566',
                'delivered_to' => 'Juan Receptor',
                'return_to_batch' => '1',
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ])
            ->assertRedirect(route('salidas.batch', [
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ]));

        $this->assertDatabaseHas('preregistrations', [
            'id' => $package->id,
            'status' => 'DELIVERED',
        ]);
        $this->assertDatabaseHas('deliveries', [
            'preregistration_id' => $package->id,
            'delivery_note_id' => $note->id,
            'delivered_to' => 'Juan Receptor',
        ]);
    }

    public function test_can_deliver_package_by_tracking(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency, [
            'tracking_external' => 'trk-delivery-abc',
            'warehouse_code' => '778899',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-9002',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->post(route('salidas.process-scan'), [
                'code' => 'trk-delivery-abc',
                'delivered_to' => 'Maria Receptor',
                'return_to_batch' => '1',
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ])
            ->assertRedirect();

        $package->refresh();
        $this->assertSame('DELIVERED', $package->status);
        $this->assertTrue(
            Delivery::where('preregistration_id', $package->id)
                ->where('delivered_to', 'Maria Receptor')
                ->whereNotNull('delivery_note_id')
                ->exists()
        );
    }

    public function test_standalone_scan_always_creates_delivery_note(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency, [
            'warehouse_code' => '112233',
            'tracking_external' => 'TRK-STANDALONE',
        ]);

        $this->actingAs($user)
            ->withSession([
                'delivery_scan_retirer' => [
                    'delivered_to' => 'Ana Solo',
                    'retirer_id_number' => '',
                    'retirer_phone' => '',
                    'invoice_number' => '',
                ],
            ])
            ->post(route('salidas.process-scan'), [
                'code' => '112233',
                'delivered_to' => 'Ana Solo',
            ])
            ->assertRedirect(route('salidas.scan'));

        $delivery = Delivery::where('preregistration_id', $package->id)->first();
        $this->assertNotNull($delivery);
        $this->assertNotNull($delivery->delivery_note_id);
        $this->assertDatabaseHas('delivery_notes', [
            'id' => $delivery->delivery_note_id,
            'agency_id' => $agency->id,
        ]);
    }

    public function test_batch_screen_lists_scanned_packages(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $this->createReadyPackage($agency, [
            'warehouse_code' => '111222',
            'tracking_external' => 'TRK-PENDING',
            'label_name' => 'Pendiente Visible',
        ]);
        $deliveredPkg = $this->createReadyPackage($agency, [
            'warehouse_code' => '333444',
            'tracking_external' => 'TRK-SCANNED',
            'label_name' => 'Ya Escaneado',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-9003',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $deliveredPkg->id,
            'delivered_at' => now(),
            'delivered_to' => 'Carlos Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $signature = hash('sha256', json_encode([
            'agency_id' => $agency->id,
            'service_type' => '',
            'delivery_note_id' => $note->id,
        ]));

        $this->actingAs($user)
            ->withSession([
                'delivery_batch_retirer' => [
                    'delivery_note_id' => $note->id,
                    'agency_id' => $agency->id,
                    'service_type' => null,
                    'signature' => $signature,
                    'delivered_to' => 'Carlos Retira',
                    'retirer_id_number' => '',
                    'retirer_phone' => '',
                    'invoice_number' => '',
                ],
            ])
            ->get(route('salidas.batch', [
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ]))
            ->assertOk()
            ->assertSee('Pendiente Visible')
            ->assertSee('Escaneados')
            ->assertSee('Ya Escaneado')
            ->assertSee('TRK-SCANNED')
            ->assertSee('Warehouse o tracking')
            ->assertDontSee('Nº factura');
    }

    public function test_batch_retirer_form_does_not_ask_for_invoice_number(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $this->createReadyPackage($agency, [
            'warehouse_code' => '111222',
            'label_name' => 'Pendiente Visible',
        ]);

        $this->actingAs($user)
            ->get(route('salidas.batch', ['agency_id' => $agency->id]))
            ->assertOk()
            ->assertSee('Hoja de salida')
            ->assertSee('Datos de quien retira')
            ->assertSee('Guardar y escanear paquetes')
            ->assertDontSee('Nº factura');
    }

    public function test_delivery_notes_index_can_search_by_code_and_warehouse(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency, [
            'warehouse_code' => '998877',
            'tracking_external' => 'TRK-SEARCH',
            'label_name' => 'Cliente Buscable',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-7777',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Pedro Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $otherAgency = $this->createAgency();
        $otherPackage = $this->createReadyPackage($otherAgency, [
            'warehouse_code' => '110011',
            'tracking_external' => 'TRK-OTHER',
            'status' => 'DELIVERED',
        ]);
        $otherNote = DeliveryNote::create([
            'code' => 'SLO-8888',
            'agency_id' => $otherAgency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $otherNote->id,
            'preregistration_id' => $otherPackage->id,
            'delivered_at' => now(),
            'delivered_to' => 'Otro',
            'delivery_type' => 'PICKUP',
        ]);

        $this->actingAs($user)
            ->get(route('salidas.index', ['q' => 'SLO-7777']))
            ->assertOk()
            ->assertSee('SLO-7777')
            ->assertDontSee('SLO-8888');

        $this->actingAs($user)
            ->get(route('salidas.index', ['q' => '998877']))
            ->assertOk()
            ->assertSee('SLO-7777')
            ->assertDontSee('SLO-8888');

        $this->actingAs($user)
            ->get(route('salidas.index', ['q' => $agency->name]))
            ->assertOk()
            ->assertSee('SLO-7777')
            ->assertDontSee('SLO-8888');

        $this->actingAs($user)
            ->get(route('salidas.index', ['q' => '7777']))
            ->assertOk()
            ->assertSee('SLO-7777')
            ->assertDontSee('SLO-8888');
    }

    public function test_salidas_index_lists_notes_and_create_button(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency, [
            'warehouse_code' => '221100',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-1212',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Luis Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $this->actingAs($user)
            ->get(route('salidas.index'))
            ->assertOk()
            ->assertSee('Crear hoja de salida')
            ->assertSee('SLO-1212')
            ->assertSee('Luis Retira')
            ->assertDontSee('Seleccione una agencia');
    }

    public function test_salidas_create_shows_ready_packages_and_start_button(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $this->createReadyPackage($agency, [
            'warehouse_code' => '334455',
            'label_name' => 'Cliente Listo',
        ]);

        $this->actingAs($user)
            ->get(route('salidas.create'))
            ->assertOk()
            ->assertSee('Crear hoja de salida')
            ->assertSee('Seleccione la agencia');

        $this->actingAs($user)
            ->get(route('salidas.create', ['agency_id' => $agency->id]))
            ->assertOk()
            ->assertSee('334455')
            ->assertSee('Cliente Listo')
            ->assertSee('Iniciar salida');
    }

    public function test_package_show_displays_linked_delivery_note(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = $this->createReadyPackage($agency, [
            'warehouse_code' => '667788',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-5555',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Luis Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('Hoja de salida')
            ->assertSee('SLO-5555')
            ->assertSee('Ver hoja');
    }

    public function test_agency_user_can_view_own_deliveries_but_cannot_create(): void
    {
        $agency = $this->createAgency();
        $other = $this->createAgency();
        $client = User::factory()->create(['agency_id' => $agency->id, 'is_admin' => false]);

        $package = $this->createReadyPackage($agency, [
            'warehouse_code' => '778899',
            'status' => 'DELIVERED',
        ]);
        $note = DeliveryNote::create([
            'code' => 'SLO-CLIENT-1',
            'agency_id' => $agency->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Luis Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $otherPackage = $this->createReadyPackage($other, [
            'warehouse_code' => '112233',
            'tracking_external' => '1ZOTHERPACKAGE01',
            'status' => 'DELIVERED',
        ]);
        $otherNote = DeliveryNote::create([
            'code' => 'SLO-OTHER-9',
            'agency_id' => $other->id,
        ]);
        Delivery::create([
            'delivery_note_id' => $otherNote->id,
            'preregistration_id' => $otherPackage->id,
            'delivered_at' => now(),
            'delivered_to' => 'Otro Retira',
            'delivery_type' => 'PICKUP',
        ]);

        $this->actingAs($client)
            ->get(route('salidas.index'))
            ->assertOk()
            ->assertSee('Mis entregas')
            ->assertSee('SLO-CLIENT-1')
            ->assertDontSee('SLO-OTHER-9')
            ->assertDontSee('Crear hoja de salida');

        $this->actingAs($client)
            ->get(route('salidas.print-report', ['delivery_note_id' => $note->id]))
            ->assertOk();

        $this->actingAs($client)
            ->get(route('salidas.print-report', ['delivery_note_id' => $otherNote->id]))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('salidas.create'))
            ->assertRedirect(route('salidas.index'));

        $this->actingAs($client)
            ->get(route('salidas.scan'))
            ->assertRedirect(route('salidas.index'));

        $this->actingAs($client)
            ->post(route('salidas.process-scan'), [
                'code' => '778899',
                'delivered_to' => 'Hack',
            ])
            ->assertRedirect(route('salidas.index'));
    }
}
