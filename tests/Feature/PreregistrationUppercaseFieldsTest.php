<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreregistrationUppercaseFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_fields_are_stored_uppercase(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Mayusculas',
            'code' => 'UP01',
            'is_active' => true,
            'is_main' => false,
        ]);

        $this->actingAs($user)
            ->post(route('preregistrations.store'), [
                'intake_type' => 'COURIER',
                'agency_id' => $agency->id,
                'label_name' => 'José Pérez',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 3.5,
                'tracking_external' => 'tba 334abc',
                'dimension' => '10 x 8 x 5 in',
                'description' => 'Ropa y electrónicos',
            ])
            ->assertRedirect();

        $package = Preregistration::where('agency_id', $agency->id)->first();
        $this->assertNotNull($package);
        $this->assertSame('JOSÉ PÉREZ', $package->label_name);
        $this->assertSame('TBA 334ABC', $package->tracking_external);
        $this->assertSame('10 X 8 X 5 IN', $package->dimension);
        $this->assertSame('ROPA Y ELECTRÓNICOS', $package->description);
        $this->assertNotNull($package->cubic_feet);
    }

    public function test_update_uppercases_text_fields(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $agency = Agency::create([
            'name' => 'Agencia Edit Mayus',
            'code' => 'UP02',
            'is_active' => true,
            'is_main' => false,
        ]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'agency_id' => $agency->id,
            'label_name' => 'Nombre viejo',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'tracking_external' => 'TRK-UP-EDIT',
            'status' => 'RECEIVED_MIAMI',
        ]);

        $this->actingAs($user)
            ->put(route('preregistrations.update', $package->id), [
                'agency_id' => $agency->id,
                'label_name' => 'Ana López',
                'service_type' => 'AIR',
                'intake_weight_lbs' => 2,
                'tracking_external' => 'trk-up-edit',
                'dimension' => '12 x 12 x 12',
                'description' => 'documentos',
            ])
            ->assertRedirect();

        $package->refresh();
        $this->assertSame('ANA LÓPEZ', $package->label_name);
        $this->assertSame('TRK-UP-EDIT', $package->tracking_external);
        $this->assertSame('12 X 12 X 12', $package->dimension);
        $this->assertSame('DOCUMENTOS', $package->description);
    }
}
