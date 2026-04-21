<?php

namespace App\Console\Commands;

use App\Models\Accountability;
use App\Models\AccountabilityDetail;
use App\Models\AccountabilityField;
use App\Models\AccountabilityLevelApproval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearAccountabilities extends Command
{
    protected $signature = 'accountabilities:clear {--force : Omitir confirmación}';

    protected $description = 'Elimina todas las solicitudes de rendición junto con sus detalles, campos y aprobaciones';

    public function handle(): int
    {
        $count = Accountability::count();

        if ($count === 0) {
            $this->info('No hay solicitudes de rendición registradas.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Se eliminarán {$count} solicitudes con todos sus detalles. ¿Continuar?")) {
            $this->line('Operación cancelada.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            AccountabilityField::truncate();
            AccountabilityLevelApproval::truncate();
            AccountabilityDetail::truncate();
            Accountability::truncate();
        });

        $this->info("Se eliminaron {$count} solicitudes correctamente.");

        return self::SUCCESS;
    }
}
