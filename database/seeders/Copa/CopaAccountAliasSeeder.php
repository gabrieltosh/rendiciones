<?php

namespace Database\Seeders\Copa;

use Illuminate\Database\Seeder;

/**
 * Carga los alias (glosas) de cuentas contables de COPA para SCZ y LPZ.
 *
 * Uso: php artisan db:seed --class="Database\\Seeders\\Copa\\CopaAccountAliasSeeder"
 */
class CopaAccountAliasSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountAliasSczSeeder::class,
            AccountAliasLpzSeeder::class,
        ]);
    }
}
