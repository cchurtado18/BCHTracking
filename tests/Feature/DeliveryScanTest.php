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
        return Agency::create([
            'name' => 'Agencia Entrega',
            'code' => 'E'.random_int(1000, 9999),
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
            'code' => 'BCH-9001',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->post(route('deliveries.process-scan'), [
                'code' => '445566',
                'delivered_to' => 'Juan Receptor',
                'return_to_batch' => '1',
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ])
            ->assertRedirect(route('deliveries.batch', [
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
            'code' => 'BCH-9002',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($user)
            ->post(route('deliveries.process-scan'), [
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
                ->exists()
        );
    }

    public function test_batch_screen_lists_scanned_packages(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $pending = $this->createReadyPackage($agency, [
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
            'code' => 'BCH-9003',
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
            ->get(route('deliveries.batch', [
                'agency_id' => $agency->id,
                'delivery_note_id' => $note->id,
            ]))
            ->assertOk()
            ->assertSee('Pendiente Visible')
            ->assertSee('Escaneados')
            ->assertSee('Ya Escaneado')
            ->assertSee('TRK-SCANNED')
            ->assertSee('Warehouse o tracking');
    }
}
