<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\ReceiptNote;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanning_a_package_records_the_sheet_and_the_user(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $package = $this->readyPackage($agency, '661122', 'TRK-AUD-SCAN-1');

        $this->actingAs($admin)
            ->post(route('salidas.process-scan'), [
                'code' => '661122',
                'delivered_to' => 'Ana Firma',
                'retirer_id_number' => '001-111111-1',
            ])
            ->assertRedirect();

        $note = DeliveryNote::query()->first();
        $this->assertNotNull($note);

        $noteLog = $this->latestAudit('delivery_note', 'created');
        $this->assertSame($admin->id, $noteLog->user_id);
        $this->assertSame($note->id, (int) $noteLog->auditable_id);
        $this->assertStringContainsString($note->code, (string) $noteLog->summary);
        $this->assertSame($note->code, $noteLog->displayCode());

        $deliveryLog = $this->latestAudit('delivery', 'created');
        $this->assertSame($admin->id, $deliveryLog->user_id);
        $this->assertStringContainsString('661122', (string) $deliveryLog->summary);
        $this->assertStringContainsString($note->code, (string) $deliveryLog->summary);
        $this->assertStringContainsString('Ana Firma', (string) $deliveryLog->summary);
        $this->assertSame('Ana Firma', $deliveryLog->snapshotGet('delivered_to'));
        $this->assertSame('661122', $deliveryLog->displayCode());

        $this->actingAs($admin)
            ->get(route('audit.index', ['search' => $note->code]))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($note->code)
            ->assertSee('Ana Firma');
    }

    public function test_updating_who_picked_up_records_old_and_new_values(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $package = $this->readyPackage($agency, '661133', 'TRK-AUD-RETIRER');
        $note = DeliveryNote::create(['code' => 'SLO-6611', 'agency_id' => $agency->id]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Nombre Viejo',
            'retirer_id_number' => '111',
            'delivery_type' => 'PICKUP',
        ]);
        $package->update(['status' => 'DELIVERED']);

        $this->actingAs($admin)
            ->put(route('salidas.hojas.update', $note), [
                'delivered_to' => 'Nombre Nuevo',
                'retirer_id_number' => '222',
                'retirer_phone' => '8888-0000',
            ])
            ->assertRedirect(route('salidas.hojas.edit', $note));

        $log = AuditLog::query()
            ->where('auditable_type', 'delivery_note')
            ->where('auditable_id', $note->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Nombre Viejo', $log->old_values['delivered_to'] ?? null);
        $this->assertSame('Nombre Nuevo', $log->new_values['delivered_to'] ?? null);
        $this->assertStringContainsString('Nombre Viejo', (string) $log->summary);
        $this->assertStringContainsString('Nombre Nuevo', (string) $log->summary);
    }

    public function test_removing_a_package_from_a_sheet_records_who_did_it_and_keeps_the_sheet(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $note = DeliveryNote::create(['code' => 'SLO-7701', 'agency_id' => $agency->id]);
        $keep = $this->readyPackage($agency, '770101', 'TRK-AUD-KEEP');
        $extra = $this->readyPackage($agency, '770102', 'TRK-AUD-EXTRA');
        $keptDelivery = Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $keep->id,
            'delivered_at' => now(),
            'delivered_to' => 'Cliente Firma',
            'delivery_type' => 'PICKUP',
        ]);
        $extraDelivery = Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $extra->id,
            'delivered_at' => now(),
            'delivered_to' => 'Cliente Firma',
            'delivery_type' => 'PICKUP',
        ]);
        $keep->update(['status' => 'DELIVERED']);
        $extra->update(['status' => 'DELIVERED']);

        $this->actingAs($admin)
            ->delete(route('salidas.hojas.remove-package', [$note, $extraDelivery]))
            ->assertRedirect(route('salidas.hojas.edit', $note));

        $this->assertDatabaseHas('delivery_notes', ['id' => $note->id, 'code' => 'SLO-7701']);
        $this->assertDatabaseHas('deliveries', ['id' => $keptDelivery->id]);
        $this->assertDatabaseMissing('deliveries', ['id' => $extraDelivery->id]);

        $log = AuditLog::query()
            ->where('auditable_type', 'delivery')
            ->where('auditable_id', $extraDelivery->id)
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('770102', (string) $log->summary);
        $this->assertStringContainsString('SLO-7701', (string) $log->summary);
        $this->assertSame('SLO-7701', $log->old_values['code'] ?? null);
        $this->assertSame('770102', $log->old_values['warehouse_code'] ?? null);

        $this->actingAs($admin)
            ->get(route('audit.show', $log))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee('770102')
            ->assertSee('SLO-7701');
    }

    public function test_blocking_the_last_package_does_not_record_a_sheet_delete(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();
        $package = $this->readyPackage($agency, '880101', 'TRK-AUD-LAST');
        $note = DeliveryNote::create(['code' => 'SLO-8801', 'agency_id' => $agency->id]);
        $delivery = Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Cliente Firma',
            'delivery_type' => 'PICKUP',
        ]);
        $package->update(['status' => 'DELIVERED']);

        $deletedBefore = AuditLog::query()->where('action', 'deleted')->count();

        $this->actingAs($admin)
            ->from(route('salidas.hojas.edit', $note))
            ->delete(route('salidas.hojas.remove-package', [$note, $delivery]))
            ->assertRedirect(route('salidas.hojas.edit', $note))
            ->assertSessionHas('error');

        $this->assertSame($deletedBefore, AuditLog::query()->where('action', 'deleted')->count());
        $this->assertDatabaseHas('delivery_notes', ['id' => $note->id]);
        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id]);
    }

    public function test_deleting_a_prealert_receipt_note_and_sack_records_the_actor(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        $prealert = Prealert::create([
            'name' => 'CLIENTE AUDITORIA',
            'agency_id' => $agency->id,
            'tracking' => 'SPXMIA012462609040001111',
            'service_type' => 'AIR',
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('prealerts.destroy', $prealert))
            ->assertRedirect(route('prealerts.index'));

        $prealertLog = $this->latestAudit('prealert', 'deleted');
        $this->assertSame($admin->id, $prealertLog->user_id);
        $this->assertStringContainsString('SPXMIA012462609040001111', (string) $prealertLog->summary);

        $receipt = ReceiptNote::create([
            'code' => 'REC-00999',
            'delivered_by' => 'Juan Dropoff',
            'agency_id' => $agency->id,
            'received_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('receipt-notes.destroy', $receipt->id))
            ->assertRedirect(route('receipt-notes.index'));

        $receiptLog = $this->latestAudit('receipt_note', 'deleted');
        $this->assertSame($admin->id, $receiptLog->user_id);
        $this->assertSame('REC-00999', $receiptLog->displayCode());

        $sack = Consolidation::create([
            'code' => 'SAC-202609-0001',
            'service_type' => 'AIR',
            'status' => 'OPEN',
        ]);
        $itemPackage = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-AUD-SACK',
            'warehouse_code' => '551122',
            'label_name' => 'En saco',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => $itemPackage->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('consolidations.destroy', $sack->id))
            ->assertRedirect(route('consolidations.index'));

        $sackLog = $this->latestAudit('consolidation', 'deleted');
        $this->assertSame($admin->id, $sackLog->user_id);
        $this->assertSame('SAC-202609-0001', $sackLog->displayCode());
        $this->assertContains('551122', $sackLog->old_values['items'] ?? []);
    }

    public function test_restoring_or_purging_a_package_is_recorded(): void
    {
        $admin = $this->admin();
        $ops = User::factory()->create([
            'agency_id' => null,
            'is_admin' => false,
            'permissions' => array_merge(Permission::operationalDefaults(), [
                Permission::ACTION_DELETE_PREREGISTRATION,
            ]),
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-AUD-RESTORE',
            'warehouse_code' => '441122',
            'label_name' => 'Recuperable',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'PHOTO_PENDING',
        ]);

        $this->actingAs($ops)
            ->delete(route('preregistrations.destroy', $package->id))
            ->assertRedirect();

        $deleted = $this->latestAudit('preregistration', 'deleted');
        $this->assertSame($ops->id, $deleted->user_id);

        $this->actingAs($admin)
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect();

        $restored = $this->latestAudit('preregistration', 'restored');
        $this->assertSame($admin->id, $restored->user_id);
        $this->assertStringContainsString('441122', (string) $restored->summary);

        $this->actingAs($ops)
            ->delete(route('preregistrations.destroy', $package->id));

        $package->refresh();
        $package->deleted_at = now()->subDays(8);
        $package->save();

        $this->actingAs($admin)
            ->post(route('audit.preregistrations.restore', $package->id))
            ->assertRedirect(route('audit.trashed'));

        $purged = $this->latestAudit('preregistration', 'force_deleted');
        $this->assertSame($admin->id, $purged->user_id);
    }

    public function test_deleting_a_user_records_the_admin_who_did_it(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create([
            'name' => 'Operador Borrable',
            'email' => 'borrar@skylink.test',
            'agency_id' => null,
            'is_admin' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $log = $this->latestAudit('user', 'deleted');
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('Operador Borrable', (string) $log->summary);
        $this->assertSame('borrar@skylink.test', $log->old_values['email'] ?? null);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'agency_id' => null,
            'is_admin' => true,
        ]);
    }

    private function agency(): Agency
    {
        $suffix = (string) random_int(1000, 9999);

        return Agency::create([
            'name' => 'Agencia Auditoria '.$suffix,
            'code' => 'A'.$suffix,
            'phone' => '2222-0000',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function readyPackage(Agency $agency, string $warehouse, string $tracking): Preregistration
    {
        return Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => $tracking,
            'warehouse_code' => $warehouse,
            'label_name' => 'Cliente Auditoria',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4,
            'status' => 'READY',
            'agency_id' => $agency->id,
            'ready_at' => now(),
        ]);
    }

    private function latestAudit(string $type, string $action): AuditLog
    {
        $log = AuditLog::query()
            ->where('auditable_type', $type)
            ->where('action', $action)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "No se encontró auditoría {$type}/{$action}");

        return $log;
    }
}
