<?php

namespace Tests\Feature;

use App\Mail\PackageAlertsDigest;
use App\Models\Agency;
use App\Models\PackageAlert;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PackageAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'agency_id' => null,
            'is_admin' => true,
            'email' => 'admin-alerts@example.com',
        ]);
    }

    private function agency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Alertas',
            'code' => 'AL01',
            'phone' => '2222-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    private function package(Agency $agency, array $overrides = []): Preregistration
    {
        return Preregistration::create(array_merge([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-AL-'.uniqid(),
            'warehouse_code' => '880'.rand(100, 999),
            'label_name' => 'Cliente Alerta',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'verified_weight_lbs' => 5,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ], $overrides));
    }

    public function test_air_package_stuck_24_hours_creates_alert_and_emails_admin(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $agency = $this->agency();
        $stuck = $this->package($agency, ['warehouse_code' => '880111']);
        $stuck->created_at = now()->subHours(25);
        $stuck->save();

        $fresh = $this->package($agency, ['warehouse_code' => '880112']);

        $this->artisan('alerts:dispatch')->assertSuccessful();

        $this->assertDatabaseHas('package_alerts', [
            'preregistration_id' => $stuck->id,
            'rule' => PackageAlert::RULE_STUCK_AIR,
        ]);
        $this->assertDatabaseMissing('package_alerts', [
            'preregistration_id' => $fresh->id,
        ]);

        Mail::assertSent(PackageAlertsDigest::class, function (PackageAlertsDigest $mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->alerts->count() === 1;
        });
    }

    public function test_alert_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('alerts:dispatch');
    }

    public function test_sea_package_needs_three_days_before_alert(): void
    {
        Mail::fake();
        $this->admin();
        $agency = $this->agency();
        $twoDays = $this->package($agency, ['service_type' => 'SEA', 'warehouse_code' => '880201']);
        $twoDays->created_at = now()->subDays(2);
        $twoDays->save();
        $fourDays = $this->package($agency, ['service_type' => 'CFT', 'warehouse_code' => '880202']);
        $fourDays->created_at = now()->subDays(4);
        $fourDays->save();

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();

        $this->assertDatabaseMissing('package_alerts', ['preregistration_id' => $twoDays->id]);
        $this->assertDatabaseHas('package_alerts', [
            'preregistration_id' => $fourDays->id,
            'rule' => PackageAlert::RULE_STUCK_SEA,
        ]);
    }

    public function test_split_lot_alerts_when_same_receipt_date_is_partially_delivered(): void
    {
        Mail::fake();
        $this->admin();
        $agency = $this->agency();
        $received = now()->subDays(5);

        $delivered = $this->package($agency, ['warehouse_code' => '880301', 'status' => 'DELIVERED']);
        $delivered->created_at = $received;
        $delivered->save();

        $left = $this->package($agency, ['warehouse_code' => '880302', 'status' => 'RECEIVED_MIAMI']);
        $left->created_at = $received;
        $left->save();

        $otherDay = $this->package($agency, ['warehouse_code' => '880303', 'status' => 'RECEIVED_MIAMI']);
        $otherDay->created_at = now()->subHours(2);
        $otherDay->save();

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();

        $this->assertDatabaseHas('package_alerts', [
            'preregistration_id' => $left->id,
            'rule' => PackageAlert::RULE_SPLIT_LOT,
        ]);
        $this->assertDatabaseMissing('package_alerts', ['preregistration_id' => $otherDay->id]);
        $this->assertDatabaseMissing('package_alerts', ['preregistration_id' => $delivered->id]);
    }

    public function test_admin_can_view_and_dismiss_alert_and_it_does_not_reopen(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $agency = $this->agency();
        $stuck = $this->package($agency, ['warehouse_code' => '880401']);
        $stuck->created_at = now()->subHours(30);
        $stuck->save();

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();
        $alert = PackageAlert::first();

        $this->actingAs($admin)
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('880401')
            ->assertSee('Aéreo parado');

        $this->actingAs($admin)
            ->post(route('alerts.dismiss', $alert))
            ->assertRedirect(route('alerts.index'));

        $this->assertNotNull($alert->fresh()->resolved_at);

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();
        $this->assertSame(1, PackageAlert::count());
        $this->assertNotNull(PackageAlert::first()->resolved_at);
    }

    public function test_non_admin_cannot_access_alerts(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);

        $this->actingAs($user)->get(route('alerts.index'))->assertRedirect(route('packages.index'));
    }

    public function test_dispatch_succeeds_when_mail_fails(): void
    {
        $this->admin();
        $agency = $this->agency();
        $stuck = $this->package($agency, ['warehouse_code' => '880777']);
        $stuck->created_at = now()->subHours(25);
        $stuck->save();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->artisan('alerts:dispatch')->assertSuccessful();

        $this->assertDatabaseHas('package_alerts', [
            'preregistration_id' => $stuck->id,
            'rule' => PackageAlert::RULE_STUCK_AIR,
        ]);
        $this->assertNull(PackageAlert::first()->emailed_at);
    }

    public function test_auto_resolved_alert_reopens_if_package_still_stuck(): void
    {
        Mail::fake();
        $this->admin();
        $agency = $this->agency();
        $stuck = $this->package($agency, ['warehouse_code' => '880501']);
        $stuck->created_at = now()->subHours(30);
        $stuck->save();

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();
        $alert = PackageAlert::first();
        $this->assertNotNull($alert);
        $alert->update(['resolved_at' => now(), 'dismissed_by' => null]);

        $this->artisan('alerts:dispatch --no-mail')->assertSuccessful();

        $this->assertSame(1, PackageAlert::count());
        $this->assertNull(PackageAlert::first()->resolved_at);
    }
}
