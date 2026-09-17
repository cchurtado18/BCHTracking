<?php

namespace App\Console\Commands;

use App\Services\PreregistrationPhotoService;
use Illuminate\Console\Command;

class DedupePreregistrationPhotos extends Command
{
    protected $signature = 'preregistrations:dedupe-photos
                            {--dry-run : Solo cuenta duplicados, no borra}
                            {--package= : Limitar a un preregistro (id)}';

    protected $description = 'Elimina fotos idénticas duplicadas solo en preregistros pendientes por completar';

    public function handle(PreregistrationPhotoService $photos): int
    {
        $packageId = $this->option('package') !== null && $this->option('package') !== ''
            ? (int) $this->option('package')
            : null;

        if ($this->option('dry-run')) {
            $preview = $photos->removeDuplicatePhotos($packageId, true);
            $this->info('Simulación: '.$preview['deleted'].' foto(s) duplicada(s) en '.$preview['packages'].' paquete(s).');
            $this->warn('Nada se borró. Quite --dry-run para eliminarlas.');

            return self::SUCCESS;
        }

        $result = $photos->removeDuplicatePhotos($packageId);
        if ($result['deleted'] === 0) {
            $this->info('No hay fotos duplicadas para eliminar.');

            return self::SUCCESS;
        }

        $this->info('Se eliminaron '.$result['deleted'].' foto(s) duplicada(s) en '.$result['packages'].' paquete(s).');

        return self::SUCCESS;
    }
}
