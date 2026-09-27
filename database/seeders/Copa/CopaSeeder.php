<?php

namespace Database\Seeders\Copa;

use Illuminate\Database\Seeder;

/**
 * Carga completa de COPA: alias de cuentas, perfiles, y usuarios con sus ciclos.
 *
 * Uso: php artisan db:seed --class="Database\\Seeders\\Copa\\CopaSeeder"
 */
class CopaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CopaAccountAliasSeeder::class,
            CopaProfileSeeder::class,
            CopaUserSeeder::class,
        ]);
    }
}
