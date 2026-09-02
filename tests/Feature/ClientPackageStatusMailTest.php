<?php

namespace Tests\Feature;

use App\Mail\PackageReadyForPickup;
use App\Mail\PackageReceivedInMiami;
use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientPackageStatusMailTest extends TestCase
{
    use RefreshDatabase;

    private function central(): User
    {
        return User::factory()->create(['agency_id' => null]);
    }

    private function agencyWithBilling(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Aviso',
            'code' => 'AV'.random_int(10, 99),
            'phone' => '2222-1111',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
            'billing_email' => 'cobro@cliente.test',
        ]);
    }

    public function test_creating_preregistration_in_miami_emails_billing_address(): void
    {
        Mail::fake();

        $agency = $this->agencyWithBilling();

        $this->actingAs($this->central())
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Cliente Miami',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 4,
                'tracking_external' => 'TRK-MIA-MAIL',
            ])
            ->assertRedirect();

        $package = Preregistration::where('tracking_external', 'TRK-MIA-MAIL')->first();
        $this->assertNotNull($package);
        $this->assertSame('RECEIVED_MIAMI', $package->status);

        Mail::assertSent(PackageReceivedInMiami::class, function (PackageReceivedInMiami $mail) use ($package) {
            return $mail->hasTo('cobro@cliente.test') && $mail->package->is($package);
        });
        Mail::assertNotSent(PackageReadyForPickup::class);
        $this->assertNotNull($package->fresh()->miami_received_notified_at);
    }

    public function test_completing_photo_pending_emails_once_and_later_edit_does_not(): void
    {
        Mail::fake();

        $agency = $this->agencyWithBilling();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-PHOTO-MAIL',
            'label_name' => 'Pendiente foto',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 3,
            'status' => 'PHOTO_PENDING',
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($this->central())
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'Pendiente foto',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 3.2,
                'tracking_external' => 'TRK-PHOTO-MAIL',
            ])
            ->assertRedirect();

        Mail::assertSent(PackageReceivedInMiami::class, 1);
        $this->assertSame('RECEIVED_MIAMI', $package->fresh()->status);
        $this->assertNotNull($package->fresh()->miami_received_notified_at);

        Mail::fake();
        $this->actingAs($this->central())
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'Nombre corregido',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 3.2,
                'tracking_external' => 'TRK-PHOTO-MAIL',
            ])
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_scan_in_nic_does_not_email_the_client(): void
    {
        Mail::fake();

        $agency = $this->agencyWithBilling();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-NIC-NOMAIL',
            'warehouse_code' => '770101',
            'label_name' => 'Cliente NIC',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 4,
            'status' => 'IN_TRANSIT',
            'agency_id' => $agency->id,
        ]);
        $sack = Consolidation::create([
            'code' => 'SAC-MAIL-001',
            'service_type' => 'AIR',
            'status' => 'SENT',
            'sent_at' => now(),
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $sack->id,
            'preregistration_id' => $package->id,
        ]);

        $this->actingAs($this->central())
            ->post(route('nic-consolidations.scan', $sack->id), ['code' => '770101'])
            ->assertRedirect(route('nic-consolidations.show', $sack->id));

        Mail::assertNothingSent();
        $this->assertSame('IN_WAREHOUSE_NIC', $package->fresh()->status);
    }

    public function test_creating_in_miami_skips_mail_when_client_has_no_billing_email(): void
    {
        Mail::fake();

        $agency = Agency::create([
            'name' => 'Sin Correo',
            'code' => 'SC'.random_int(10, 99),
            'phone' => '2222-0000',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->actingAs($this->central())
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Sin mail',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 3,
                'tracking_external' => 'TRK-NO-MAIL',
            ])
            ->assertRedirect();

        Mail::assertNothingSent();
        $this->assertSame('RECEIVED_MIAMI', Preregistration::where('tracking_external', 'TRK-NO-MAIL')->value('status'));
        $this->assertNull(Preregistration::where('tracking_external', 'TRK-NO-MAIL')->value('miami_received_notified_at'));
    }

    public function test_marking_ready_emails_billing_address_and_reprint_does_not(): void
    {
        Mail::fake();

        $agency = $this->agencyWithBilling();
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-READY-MAIL',
            'warehouse_code' => '770303',
            'label_name' => 'Cliente Retiro',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 6,
            'status' => 'IN_WAREHOUSE_NIC',
            'received_nic_at' => now()->subHour(),
            'agency_id' => $agency->id,
        ]);

        $this->actingAs($this->central())
            ->post(route('packages.process.store', $package->id), [
                'verified_weight_lbs' => 6.2,
            ])
            ->assertRedirect(route('packages.show', $package->id));

        Mail::assertSent(PackageReadyForPickup::class, function (PackageReadyForPickup $mail) {
            return $mail->hasTo('cobro@cliente.test') && $mail->packageCode === '770303';
        });
        Mail::assertNotSent(PackageReceivedInMiami::class);
        $this->assertSame('READY', $package->fresh()->status);
        $this->assertNotNull($package->fresh()->ready_notified_at);

        Mail::fake();
        $this->actingAs($this->central())
            ->post(route('packages.reprint-label', $package->id))
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_miami_receipt_still_saves_if_mail_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Resend down'));

        $agency = $this->agencyWithBilling();

        $this->actingAs($this->central())
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'Falla mail',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 2,
                'tracking_external' => 'TRK-MAIL-FAIL',
            ])
            ->assertRedirect();

        $package = Preregistration::where('tracking_external', 'TRK-MAIL-FAIL')->first();
        $this->assertNotNull($package);
        $this->assertSame('RECEIVED_MIAMI', $package->status);
        $this->assertNull($package->miami_received_notified_at);
    }
}
