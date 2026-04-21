<?php

namespace App\Console\Commands;

use App\Models\AccountAlias;
use Illuminate\Console\Command;

class ClearAccountAliases extends Command
{
    protected $signature = 'aliases:clear {--force : Omitir confirmación}';

    protected $description = 'Elimina todos los alias de cuentas contables';

    public function handle(): int
    {
        $count = AccountAlias::count();

        if ($count === 0) {
            $this->info('No hay alias registrados.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Se eliminarán {$count} alias. ¿Continuar?")) {
            $this->line('Operación cancelada.');
            return self::SUCCESS;
        }

        AccountAlias::truncate();
        $this->info("Se eliminaron {$count} alias correctamente.");

        return self::SUCCESS;
    }
}
