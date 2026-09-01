<?php

namespace Database\Seeders;

use App\Models\AccountingRateCard;
use App\Models\Agency;
use App\Models\AgencyClient;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos locales para probar Salidas y Facturas PrimeTrack.
 * Idempotente: se puede volver a correr sin duplicar.
 */
class DemoAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $slo = Agency::query()->where('code', '0001')->orWhere('name', 'SkyLink One')->orderBy('id')->first();
        $ch = Agency::query()->where('code', '0002')->orWhere('name', 'CH LOGISTICS')->orderBy('id')->first();

        if (! $slo || ! $ch) {
            $this->command?->error('Faltan SkyLink One (0001) o CH LOGISTICS (0002).');

            return;
        }

        $norte = Agency::firstOrCreate(
            ['name' => 'Norte Express Demo'],
            [
                'code' => Agency::nextAvailableNumericCode(),
                'phone' => '8888-1000',
                'address' => 'Km 12 Carretera Norte',
                'department' => 'Managua',
                'is_active' => true,
                'is_main' => false,
                'account_type' => Agency::TYPE_SUBAGENCY,
                'parent_agency_id' => $ch->id,
            ]
        );

        $maria = Agency::firstOrCreate(
            ['name' => 'María López Demo'],
            [
                'code' => Agency::nextAvailableNumericCode(),
                'phone' => '8888-2000',
                'address' => 'Residencial Las Palmas',
                'department' => 'Managua',
                'is_active' => true,
                'is_main' => false,
                'account_type' => Agency::TYPE_DIRECT_CLIENT,
                'parent_agency_id' => $slo->id,
            ]
        );

        $this->ensureUser('norte@demo.local', 'Norte Express', $norte->id, false);
        $this->ensureUser('maria@demo.local', 'María López', $maria->id, false);

        $destCh = AgencyClient::firstOrCreate(
            ['agency_id' => $ch->id, 'full_name' => 'Rosa Amelia Demo'],
            ['phone' => '8888-3001', 'is_active' => true]
        );
        $destNorte = AgencyClient::firstOrCreate(
            ['agency_id' => $norte->id, 'full_name' => 'Luis Mendoza Demo'],
            ['phone' => '8888-3002', 'is_active' => true]
        );

        $this->ensureRate($ch->id, 'AIR', 3.50, 1.80);
        $this->ensureRate($ch->id, 'SEA', 1.25, 0.60);
        $this->ensureRate($norte->id, 'AIR', 3.75, 1.90);
        $this->ensureRate($norte->id, 'SEA', 1.40, 0.70);
        $this->ensureRate($maria->id, 'AIR', 4.00, 2.00);

        $ready = [
            ['880101', 'TRK-DEMO-AIR-CH-1', 'Rosa Amelia Demo', 'AIR', 8.5, 9.0, $ch->id, $destCh->id],
            ['880102', 'TRK-DEMO-SEA-CH-1', 'Rosa Amelia Demo', 'SEA', 22.0, null, $ch->id, $destCh->id],
            ['880103', 'TRK-DEMO-AIR-NO-1', 'Luis Mendoza Demo', 'AIR', 6.0, 6.2, $norte->id, $destNorte->id],
            ['880104', 'TRK-DEMO-SEA-NO-1', 'Luis Mendoza Demo', 'SEA', 18.0, null, $norte->id, $destNorte->id],
            ['880105', 'TRK-DEMO-AIR-ML-1', 'María López Demo', 'AIR', 4.5, 4.8, $maria->id, null],
        ];

        foreach ($ready as [$wh, $trk, $name, $svc, $intake, $verified, $agencyId, $clientId]) {
            $this->ensurePackage($wh, $trk, $name, $svc, $intake, $verified, $agencyId, $clientId, 'READY');
        }

        $note = DeliveryNote::firstOrCreate(
            ['code' => 'SLO-8801'],
            ['agency_id' => $ch->id]
        );

        $delivered = [
            ['880201', 'TRK-DEMO-AIR-OUT-1', 'Carlos Demo', 'AIR', 12.0, 12.0, $ch->id],
            ['880202', 'TRK-DEMO-SEA-OUT-1', 'Brenda Demo', 'SEA', 20.0, null, $ch->id],
        ];

        foreach ($delivered as [$wh, $trk, $name, $svc, $intake, $verified, $agencyId]) {
            $pkg = $this->ensurePackage($wh, $trk, $name, $svc, $intake, $verified, $agencyId, null, 'DELIVERED');
            Delivery::firstOrCreate(
                ['preregistration_id' => $pkg->id],
                [
                    'delivery_note_id' => $note->id,
                    'delivered_at' => now()->subHours(2),
                    'delivered_to' => 'Juan Retira Demo',
                    'retirer_id_number' => '001-000000-0000X',
                    'retirer_phone' => '8888-4000',
                    'delivery_type' => 'PICKUP',
                ]
            );
        }

        $this->command?->info('Datos demo listos.');
        $this->command?->line('  Salidas nuevas: agencia CH Logistics o Norte Express Demo (paquetes READY).');
        $this->command?->line('  Factura: hoja SLO-8801 (CH, AIR+SEA).');
        $this->command?->line('  Logins extra: norte@demo.local / maria@demo.local  contraseña: password12');
        $this->command?->line('  Admin sigue: admin@bch.local / admin123');
    }

    private function ensureUser(string $email, string $name, int $agencyId, bool $admin): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password12'),
                'is_admin' => $admin,
                'agency_id' => $agencyId,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->update([
                'name' => $name,
                'agency_id' => $agencyId,
                'is_admin' => $admin,
            ]);
        }
    }

    private function ensureRate(int $agencyId, string $service, float $price, float $cost): void
    {
        $current = AccountingRateCard::currentFor($agencyId, $service);
        if ($current) {
            return;
        }

        AccountingRateCard::create([
            'agency_id' => $agencyId,
            'service_type' => $service,
            'price_per_lb' => $price,
            'cost_per_lb' => $cost,
            'currency' => 'USD',
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'created_by' => User::query()->where('is_admin', true)->value('id'),
        ]);
    }

    private function ensurePackage(
        string $warehouse,
        string $tracking,
        string $label,
        string $service,
        float $intake,
        ?float $verified,
        int $agencyId,
        ?int $clientId,
        string $status
    ): Preregistration {
        $pkg = Preregistration::withTrashed()->firstOrNew(['warehouse_code' => $warehouse]);
        $pkg->fill([
            'intake_type' => 'COURIER',
            'tracking_external' => $tracking,
            'label_name' => $label,
            'service_type' => $service,
            'intake_weight_lbs' => $intake,
            'verified_weight_lbs' => $verified,
            'dimension' => '12 x 10 x 8',
            'description' => 'Paquete demo para pruebas',
            'status' => $status,
            'agency_id' => $agencyId,
            'agency_client_id' => $clientId,
            'ready_at' => now()->subDay(),
        ]);
        if ($pkg->trashed()) {
            $pkg->restore();
        }
        $pkg->save();

        return $pkg;
    }
}
