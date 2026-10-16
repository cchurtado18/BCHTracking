<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DropoffResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_resume_an_incomplete_dropoff_after_leaving(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();

        $this->actingAs($user);
        $this->postNextDropoffBulto($agency, 1, 4, 'ANA UNO');
        $this->postNextDropoffBulto($agency, 2, 4, 'ANA DOS');

        $first = Preregistration::query()->where('label_name', 'ANA UNO')->first();
        $this->assertNotNull($first);
        $this->assertSame(2, $first->dropoffGroupMembers()->count());
        $this->assertSame(3, $first->nextMissingDropoffIndex());

        $this->flushSession();

        $this->actingAs($user)
            ->get(route('preregistrations.show', $first->id))
            ->assertOk()
            ->assertSee('Continuar bulto 3/4')
            ->assertSee('Este Drop Off está incompleto');

        $this->actingAs($user)
            ->get(route('preregistrations.continue-dropoff', $first->id))
            ->assertRedirect(route('preregistrations.create'));

        $this->actingAs($user)
            ->get(route('preregistrations.create'))
            ->assertOk()
            ->assertSee('Bulto 3 de 4')
            ->assertSee($first->warehouse_code);

        $this->postNextDropoffBulto($agency, 3, 4, 'ANA TRES');

        $this->assertSame(3, $first->fresh()->dropoffGroupMembers()->count());
        $this->assertTrue(
            Preregistration::query()
                ->where('warehouse_code', $first->warehouse_code)
                ->where('bulto_index', 3)
                ->exists()
        );
    }

    public function test_complete_dropoff_cannot_be_resumed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'warehouse_code' => '881122',
            'agency_id' => $agency->id,
            'label_name' => 'Completo Uno',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'bulto_index' => 1,
            'bultos_total' => 2,
        ]);
        Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'warehouse_code' => '881122',
            'agency_id' => $agency->id,
            'label_name' => 'Completo Dos',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'bulto_index' => 2,
            'bultos_total' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.continue-dropoff', $package->id))
            ->assertRedirect(route('preregistrations.show', $package->id))
            ->assertSessionHas('error');
    }

    public function test_label_offers_continue_from_saved_dropoff_without_session(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['agency_id' => null]);
        $agency = $this->createAgency();
        $package = Preregistration::create([
            'intake_type' => 'DROP_OFF',
            'warehouse_code' => '883344',
            'agency_id' => $agency->id,
            'label_name' => 'Etiqueta Drop',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 2,
            'status' => 'RECEIVED_MIAMI',
            'bulto_index' => 1,
            'bultos_total' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('preregistrations.label', $package->id))
            ->assertOk()
            ->assertSee(route('preregistrations.continue-dropoff', $package->id), false)
            ->assertSee('Continuar con el siguiente bulto (2/3)');
    }

    private function postNextDropoffBulto(Agency $agency, int $step, int $total, string $name): void
    {
        $payload = [
            'intake_type' => 'DROP_OFF',
            'dropoff_step' => $step,
            'bultos_count' => $total,
            'label_name' => $name,
            'intake_weight_lbs' => 1.5,
            'dimension' => '10 x 8 x 5 in',
            'photo' => UploadedFile::fake()->image('bulto-'.$step.'.jpg', 240, 240),
        ];
        if ($step === 1) {
            $payload['agency_id'] = $agency->id;
            $payload['service_type'] = 'AIR';
        }

        $this->post(route('preregistrations.store'), $payload)->assertRedirect();
    }

    private function createAgency(): Agency
    {
        return Agency::create([
            'name' => 'Agencia Resume',
            'code' => 'AR'.random_int(1000, 9999),
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);
    }
}
