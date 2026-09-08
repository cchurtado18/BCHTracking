<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\ReceiptNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptNoteSloClientsTest extends TestCase
{
    use RefreshDatabase;

    private function sloAndClient(): array
    {
        $slo = Agency::query()->where('code', '0001')->first()
            ?? Agency::query()->where('name', 'SkyLink One')->first();
        $this->assertNotNull($slo);

        $client = Agency::create([
            'name' => 'Cliente Final Recibo SLO',
            'code' => 'R'.random_int(1000, 9999),
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_DIRECT_CLIENT,
            'parent_agency_id' => $slo->id,
        ]);

        $sub = Agency::create([
            'name' => 'Subagencia Recibo Test',
            'code' => 'S'.random_int(1000, 9999),
            'is_active' => true,
            'is_main' => false,
            'account_type' => Agency::TYPE_SUBAGENCY,
            'parent_agency_id' => $slo->id,
        ]);

        return compact('slo', 'client', 'sub');
    }

    public function test_create_form_lists_slo_clients_apart_from_agencies(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'client' => $client, 'sub' => $sub] = $this->sloAndClient();

        $html = $this->actingAs($admin)
            ->get(route('receipt-notes.batch'))
            ->assertOk()
            ->assertSee('Cuenta')
            ->assertSee('Cliente de SkyLink One')
            ->assertSee($slo->name)
            ->assertSee($client->name)
            ->assertSee($sub->name)
            ->getContent();

        preg_match('/<select[^>]*id="partner_agency_id"[\s\S]*?<\/select>/', $html, $partnerSelect);
        $this->assertNotEmpty($partnerSelect);
        $this->assertStringNotContainsString('value="'.$client->id.'"', $partnerSelect[0]);

        preg_match('/<select[^>]*id="slo_client_id"[\s\S]*?<\/select>/', $html, $clientSelect);
        $this->assertNotEmpty($clientSelect);
        $this->assertStringContainsString('value="'.$client->id.'"', $clientSelect[0]);
    }

    public function test_store_requires_slo_client_not_slo_as_generic_account(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'client' => $client] = $this->sloAndClient();

        $this->actingAs($admin)
            ->from(route('receipt-notes.batch'))
            ->post(route('receipt-notes.store'), [
                'delivered_by' => 'Juan Entrega',
                'agency_id' => $slo->id,
            ])
            ->assertRedirect(route('receipt-notes.batch'))
            ->assertSessionHasErrors('agency_id');

        $this->actingAs($admin)
            ->post(route('receipt-notes.store'), [
                'delivered_by' => 'Juan Entrega',
                'agency_id' => $client->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('receipt_notes', [
            'delivered_by' => 'Juan Entrega',
            'agency_id' => $client->id,
        ]);
    }

    public function test_print_shows_slo_as_account_and_client_name(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'client' => $client, 'sub' => $sub] = $this->sloAndClient();

        $note = ReceiptNote::create([
            'code' => ReceiptNote::generateCode(),
            'delivered_by' => 'Ana Dropoff',
            'agency_id' => $client->id,
            'received_by_user_id' => $admin->id,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('receipt-notes.print', $note->id))
            ->assertOk()
            ->assertSee($client->name)
            ->getContent();

        $this->assertStringContainsString('SKYLINK ONE', $html);
        $this->assertStringContainsString($client->name, $html);

        $subNote = ReceiptNote::create([
            'code' => ReceiptNote::generateCode(),
            'delivered_by' => 'Pedro Sub',
            'agency_id' => $sub->id,
            'received_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('receipt-notes.print', $subNote->id))
            ->assertOk()
            ->assertSee(strtoupper($sub->name))
            ->assertSee($sub->name)
            ->assertDontSee($client->name);
    }

    public function test_index_lists_slo_client_under_skylink_one_account(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['slo' => $slo, 'client' => $client] = $this->sloAndClient();

        ReceiptNote::create([
            'code' => ReceiptNote::generateCode(),
            'delivered_by' => 'Ana Dropoff',
            'agency_id' => $client->id,
            'received_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('receipt-notes.index'))
            ->assertOk()
            ->assertSee('SkyLink One · '.$client->name)
            ->assertDontSee('>'.$client->name.'</option>', false);

        $this->actingAs($admin)
            ->get(route('receipt-notes.index', ['agency_id' => $slo->id]))
            ->assertOk()
            ->assertSee('SkyLink One · '.$client->name);
    }

    public function test_can_add_slo_client_dropoff_to_that_clients_receipt(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        ['client' => $client] = $this->sloAndClient();

        $note = ReceiptNote::create([
            'code' => ReceiptNote::generateCode(),
            'delivered_by' => 'Ana Dropoff',
            'agency_id' => $client->id,
            'received_by_user_id' => $admin->id,
        ]);

        $pkg = Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'tracking_external' => 'TRK-REC-SLO-1',
            'warehouse_code' => '771122',
            'label_name' => 'Destinatario Recibo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $client->id,
        ]);

        $this->actingAs($admin)
            ->post(route('receipt-notes.add-item', $note->id), [
                'preregistration_id' => $pkg->id,
            ])
            ->assertRedirect(route('receipt-notes.batch', ['receipt_note_id' => $note->id]));

        $this->assertSame($note->id, $pkg->fresh()->receipt_note_id);
    }
}
