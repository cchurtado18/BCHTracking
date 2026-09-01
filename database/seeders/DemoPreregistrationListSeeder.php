<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Preregistration;
use Illuminate\Database\Seeder;

/**
 * Llena el listado de preregistros con datos de demo (idempotente por warehouse_code).
 */
class DemoPreregistrationListSeeder extends Seeder
{
    public function run(): void
    {
        $agencies = Agency::query()
            ->where('is_active', true)
            ->where('is_main', false)
            ->orderBy('id')
            ->get();

        if ($agencies->isEmpty()) {
            $agencies = Agency::query()->where('is_active', true)->orderBy('id')->get();
        }

        if ($agencies->isEmpty()) {
            $this->command?->error('No hay agencias activas para asignar preregistros.');

            return;
        }

        $names = [
            'Carlos Méndez', 'Ana Rojas', 'José Vargas', 'María López', 'Luis Pérez',
            'Rosa Amelia', 'Brenda Castro', 'Pedro Gómez', 'Sofía Ruiz', 'Diego Morales',
            'Elena Cruz', 'Andrés Soto', 'Karla Díaz', 'Miguel Ángel', 'Patricia Núñez',
            'Fernando Silva', 'Gabriela Torrez', 'Héctor Ramos', 'Irene Aguilar', 'Javier León',
        ];

        $statuses = [
            'RECEIVED_MIAMI', 'RECEIVED_MIAMI', 'RECEIVED_MIAMI',
            'IN_TRANSIT', 'IN_TRANSIT',
            'IN_WAREHOUSE_NIC',
            'READY', 'READY',
            'DELIVERED',
            'PHOTO_PENDING',
        ];

        $created = 0;

        for ($i = 1; $i <= 50; $i++) {
            $warehouse = '89'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $agency = $agencies[($i - 1) % $agencies->count()];
            $service = $i % 3 === 0 ? 'SEA' : 'AIR';
            $status = $statuses[($i - 1) % count($statuses)];
            $name = $names[($i - 1) % count($names)];
            $intake = round(3 + ($i % 25) + (($i % 7) * 0.15), 2);
            $daysAgo = $i % 20;

            $pkg = Preregistration::withTrashed()->firstOrNew(['warehouse_code' => $warehouse]);
            $wasNew = ! $pkg->exists;

            $pkg->fill([
                'intake_type' => $i % 4 === 0 ? 'DROP_OFF' : 'COURIER',
                'tracking_external' => 'TRK-LIST-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'label_name' => $status === 'PHOTO_PENDING' ? '[PENDIENTE]' : $name,
                'service_type' => $service,
                'intake_weight_lbs' => $intake,
                'verified_weight_lbs' => $status === 'READY' || $status === 'DELIVERED' ? $intake : null,
                'dimension' => '12 x 10 x 8',
                'description' => 'Paquete demo listado',
                'status' => $status,
                'agency_id' => $status === 'PHOTO_PENDING' ? null : $agency->id,
                'ready_at' => in_array($status, ['READY', 'DELIVERED'], true) ? now()->subDays(max(1, $daysAgo)) : null,
            ]);

            if ($pkg->trashed()) {
                $pkg->restore();
            }

            $pkg->save();

            // Variar fechas de ingreso para filtros
            Preregistration::whereKey($pkg->id)->update([
                'created_at' => now()->subDays($daysAgo)->subMinutes($i * 3),
                'updated_at' => now()->subDays($daysAgo),
            ]);

            if ($wasNew) {
                $created++;
            }
        }

        $total = Preregistration::count();
        $this->command?->info("Listado demo listo. Nuevos: {$created}. Total preregistros: {$total}.");
    }
}
