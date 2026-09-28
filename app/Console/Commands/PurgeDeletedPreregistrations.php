<?php

namespace App\Console\Commands;

use App\Services\PreregistrationTrashService;
use Illuminate\Console\Command;

class PurgeDeletedPreregistrations extends Command
{
    protected $signature = 'preregistrations:purge-deleted';

    protected $description = 'Elimina de forma definitiva los preregistros borrados cuyo plazo de recuperación ya venció';

    public function handle(PreregistrationTrashService $trash): int
    {
        $purged = $trash->purgeExpired();

        if ($purged === 0) {
            $this->info('No hay preregistros vencidos para eliminar.');

            return self::SUCCESS;
        }

        $this->info($purged.' preregistro(s) eliminado(s) de forma definitiva.');

        return self::SUCCESS;
    }
}
