<?php

namespace App\Console\Commands;

use App\Models\Delivery;
use App\Models\DeliveryNote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkOrphanDeliveriesToNotes extends Command
{
    protected $signature = 'deliveries:link-orphan-notes
                            {--dry-run : Solo muestra cuántas entregas quedarían vinculadas}';

    protected $description = 'Vincula entregas históricas sin nota de salida a notas BCH (agrupa por agencia, fecha y quien retira)';

    public function handle(): int
    {
        $orphans = Delivery::query()
            ->with('preregistration')
            ->whereNull('delivery_note_id')
            ->orderBy('delivered_at')
            ->orderBy('id')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No hay entregas sin nota de salida.');

            return self::SUCCESS;
        }

        $groups = $orphans->groupBy(function (Delivery $delivery) {
            $agencyId = (int) ($delivery->preregistration?->agency_id ?? 0);
            $date = optional($delivery->delivered_at)->toDateString() ?: 'unknown';
            $retirer = trim((string) ($delivery->delivered_to ?? ''));

            return $agencyId.'|'.$date.'|'.mb_strtolower($retirer);
        });

        $this->info($orphans->count().' entregas huérfanas en '.$groups->count().' grupos.');

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $linked = 0;
        $notesCreated = 0;

        DB::transaction(function () use ($groups, &$linked, &$notesCreated) {
            foreach ($groups as $group) {
                /** @var \Illuminate\Support\Collection<int, Delivery> $group */
                $first = $group->first();
                $agencyId = $first?->preregistration?->agency_id;

                $note = DeliveryNote::create([
                    'code' => DeliveryNote::generateCode(),
                    'agency_id' => $agencyId,
                ]);
                $notesCreated++;

                foreach ($group as $delivery) {
                    $delivery->update(['delivery_note_id' => $note->id]);
                    $linked++;
                }
            }
        });

        $this->info("Listo: {$notesCreated} notas creadas, {$linked} entregas vinculadas.");

        return self::SUCCESS;
    }
}
