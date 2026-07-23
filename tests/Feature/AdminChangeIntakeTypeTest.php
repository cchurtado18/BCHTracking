<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChangeIntakeTypeTest extends TestCase
{
    use RefreshDatabase;

    private function createPackage(string $intakeType = 'DROP_OFF'): Preregistration
    {
        $agency = Agency::create([
            'name' => 'Agencia Tipo',
            'code' => 'T'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);

        return Preregistration::create([
            'intake_type' => $intakeType,
            'tracking_external' => 'TRK-INTAKE-001',
            'warehouse_code' => '556677',
            'label_name' => 'Cliente Tipo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 5,
            'status' => 'RECEIVED_MIAMI',
            'agency_id' => $agency->id,
        ]);
    }

    public function test_admin_can_change_drop_off_to_courier(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $package = $this->createPackage('DROP_OFF');

        $this->actingAs($admin)
            ->post(route('preregistrations.admin.intake-type', $package->id), [
                'intake_type' => 'COURIER',
                'return_to' => 'package',
            ])
            ->assertRedirect(route('packages.show', $package->id));

        $this->assertSame('COURIER', $package->fresh()->intake_type);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $package->id,
            'action' => 'admin_change_intake_type',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_change_courier_to_drop_off(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $package = $this->createPackage('COURIER');

        $this->actingAs($admin)
            ->post(route('preregistrations.admin.intake-type', $package->id), [
                'intake_type' => 'DROP_OFF',
            ])
            ->assertRedirect(route('preregistrations.show', $package->id));

        $this->assertSame('DROP_OFF', $package->fresh()->intake_type);
    }

    public function test_non_admin_cannot_change_intake_type(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        $package = $this->createPackage('DROP_OFF');

        $this->actingAs($user)
            ->post(route('preregistrations.admin.intake-type', $package->id), [
                'intake_type' => 'COURIER',
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertSame('DROP_OFF', $package->fresh()->intake_type);
        $this->assertSame(0, AuditLog::where('action', 'admin_change_intake_type')->count());
    }

    public function test_admin_panel_visible_only_for_admin_on_package_show(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        $package = $this->createPackage('DROP_OFF');

        $this->actingAs($admin)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertSee('Cambiar')
            ->assertSee('Tipo de ingreso');

        $this->actingAs($user)
            ->get(route('packages.show', $package->id))
            ->assertOk()
            ->assertDontSee('admin-intake-panel__btn', false);
    }
}
