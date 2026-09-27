<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Vacía la base de datos y deja un único usuario administrador.
 *
 * Conserva `migrations` y `management` (configuración de la empresa, SAP y UDF);
 * con --keep se pueden conservar más tablas.
 *
 * Uso: php artisan db:reset-admin
 *      php artisan db:reset-admin --keep=account_aliases --keep=areas
 */
class ResetDatabaseToAdmin extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db:reset-admin
        {--username=admin : Usuario del administrador}
        {--password=Rend123 : Contraseña del administrador}
        {--name=Admin : Nombre del administrador}
        {--email=admin@rendiciones.local : Correo del administrador}
        {--keep=* : Tablas adicionales que no se vacían}
        {--force : Omitir confirmación}';

    protected $description = 'Vacía la base de datos (salvo la configuración) y deja solo el usuario administrador';

    private const KEEP_TABLES = ['migrations', 'management', 'sysdiagrams'];

    public function handle(): int
    {
        $keep = array_map('strtolower', array_merge(self::KEEP_TABLES, $this->option('keep')));
        $tables = array_values(array_filter(
            $this->getTables(),
            fn($table) => !in_array(strtolower($table), $keep, true)
        ));

        $this->warn('Se vaciarán ' . count($tables) . ' tablas: ' . implode(', ', $tables));
        $this->line('Se conservan: ' . implode(', ', $keep));

        if (!$this->option('force') && !$this->confirm('Esta acción no se puede deshacer. ¿Continuar?')) {
            $this->line('Operación cancelada.');
            return self::SUCCESS;
        }
        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
                $this->resetIdentity($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        User::disableAuditing();
        User::create([
            'name'     => $this->option('name'),
            'email'    => $this->option('email'),
            'username' => $this->option('username'),
            'type'     => 'Administrador',
            'status'   => 'Activo',
            'password' => Hash::make($this->option('password')),
        ]);
        User::enableAuditing();

        $this->info("Base de datos limpia. Usuario \"{$this->option('username')}\" creado.");

        return self::SUCCESS;
    }

    private function getTables(): array
    {
        return DB::table('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->when(DB::getDriverName() === 'mysql', fn($q) => $q->where('TABLE_SCHEMA', DB::getDatabaseName()))
            ->pluck('TABLE_NAME')
            ->all();
    }

    /** Reinicia los autoincrementales para que el admin quede con id 1. */
    private function resetIdentity(string $table): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            // Solo si la identidad ya se usó: en una tabla que nunca tuvo filas,
            // RESEED 0 haría que el primer registro quede con id 0.
            $identity = DB::selectOne('SELECT last_value FROM sys.identity_columns WHERE object_id = OBJECT_ID(?)', [$table]);
            if ($identity && $identity->last_value !== null) {
                DB::statement("DBCC CHECKIDENT ('[{$table}]', RESEED, 0)");
            }
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        }
    }
}
