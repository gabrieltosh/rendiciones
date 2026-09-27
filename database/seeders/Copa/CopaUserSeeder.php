<?php

namespace Database\Seeders\Copa;

use App\Models\AuthorizationCycle;
use App\Models\AuthorizationCycleLevel;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserAuthorizationCycle;
use App\Models\UserProfile;
use Hash;
use Illuminate\Database\Seeder;

/**
 * Usuarios de registro y ciclos de autorización de COPA.
 *
 * Fuente: "Datos Rendiciones -Administrativo - Actualizado.xlsx", hoja
 * "TIPOS DE RENDICION ADMINISTRATI" (columnas NIVELES DE AUTORIZACION y
 * USUARIOS DE REGISTRO). Cada usuario queda vinculado a los perfiles que
 * registra y al ciclo del jefe que los aprueba.
 *
 * Los ciclos se crean con un nivel y sin aprobadores: se asignan después
 * desde el panel. "USUARIOS DE VIATICOS" no es una persona, así que el
 * perfil VIATICOS-VIAJES A SCZ queda sin usuarios.
 *
 * Requiere haber ejecutado antes CopaProfileSeeder. Es idempotente.
 *
 * Uso: php artisan db:seed --class="Database\\Seeders\\Copa\\CopaUserSeeder"
 */
class CopaUserSeeder extends Seeder
{
    private const PASSWORD = 'Rend123';

    /** Dominio provisional: reemplazar por el correo real de cada usuario. */
    private const EMAIL_DOMAIN = 'copabol.local';

    private const JEFA_COMPRAS = 'JEFA DE COMPRAS';
    private const JEFE_CONTABILIDAD = 'JEFE DE CONTABILIDAD';
    private const JEFE_RRHH = 'JEFE DE RECURSOS HUMANOS';
    private const JEFE_PROCESO_POLLO = 'JEFE DE PROCESO POLLO';
    private const JEFE_CONTROL_CALIDAD = 'JEFE DE CONTROL DE CALIDAD';
    private const JEFE_MANTENIMIENTO = 'JEFE DE MANTENIMIENTO';
    private const JEFE_ADMINISTRACION = 'JEFE DE ADMINISTRACION';

    private function users(): array
    {
        return [
            ['name' => 'Sixto Yujra', 'username' => 'sixto.yujra', 'cycle' => self::JEFA_COMPRAS,
                'profiles' => ['CAJA CHICA COMPRAS']],
            ['name' => 'Victor Lucana', 'username' => 'victor.lucana', 'cycle' => self::JEFE_CONTABILIDAD,
                'profiles' => ['CAJA CHICA CONTABILIDAD']],
            ['name' => 'Lucero Primicia', 'username' => 'lucero.primicia', 'cycle' => self::JEFE_RRHH,
                'profiles' => ['CAJA CHICA RR.HH']],
            ['name' => 'Marcelo Valenzuela', 'username' => 'marcelo.valenzuela', 'cycle' => self::JEFE_PROCESO_POLLO,
                'profiles' => ['CAJA CHICA PROCESO POLLO COMBUS', 'CAJA CHICA PROCESO POLLO']],
            ['name' => 'Jorge Guerrero', 'username' => 'jorge.guerrero', 'cycle' => self::JEFE_CONTROL_CALIDAD,
                'profiles' => ['CAJA CHICA CONTROL DE CALIDAD']],
            ['name' => 'Carola Zeballos', 'username' => 'carola.zeballos', 'cycle' => self::JEFE_CONTROL_CALIDAD,
                'profiles' => ['CAJA CHICA GESTION DE CALIDAD']],
            ['name' => 'Victor Takkusi', 'username' => 'victor.takkusi', 'cycle' => self::JEFE_MANTENIMIENTO,
                'profiles' => ['CAJA CHICA MTTO']],
            ['name' => 'Antonio Sainz', 'username' => 'antonio.sainz', 'cycle' => self::JEFE_ADMINISTRACION,
                'profiles' => ['GASTOS DE ADM', 'GASTOS TRAMITES BETTY BOSQUE']],
            ['name' => 'Bety Bosque', 'username' => 'bety.bosque', 'cycle' => self::JEFE_ADMINISTRACION,
                'profiles' => ['GASTOS TRAMITES BETTY BOSQUE']],
            ['name' => 'Betty Arias', 'username' => 'betty.arias', 'cycle' => self::JEFE_ADMINISTRACION,
                'profiles' => ['BETY ARIAS-REMMB COMBUSTIBLES']],
        ];
    }

    public function run(): void
    {
        // "JEFA DE RECURSOS HUMANOS" (viáticos) se unifica con "JEFE DE RECURSOS HUMANOS".
        $cycles = [];
        foreach ([
            self::JEFA_COMPRAS,
            self::JEFE_CONTABILIDAD,
            self::JEFE_RRHH,
            self::JEFE_PROCESO_POLLO,
            self::JEFE_CONTROL_CALIDAD,
            self::JEFE_MANTENIMIENTO,
            self::JEFE_ADMINISTRACION,
        ] as $name) {
            $cycles[$name] = $this->cycle($name);
        }

        foreach ($this->users() as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['username'] . '@' . self::EMAIL_DOMAIN,
                    'type' => 'Usuario',
                    'status' => 'Activo',
                    'password' => Hash::make(self::PASSWORD),
                ]
            );

            foreach ($data['profiles'] as $profileName) {
                $profile = Profile::where('name', $profileName)->first();
                if (!$profile) {
                    $this->command?->warn("Perfil \"{$profileName}\" no existe (ejecute CopaProfileSeeder); no se vinculó a {$data['username']}.");
                    continue;
                }
                UserProfile::firstOrCreate(['user_id' => $user->id, 'profile_id' => $profile->id]);
            }

            // El sistema admite un solo ciclo por usuario.
            if (!UserAuthorizationCycle::where('user_id', $user->id)->exists()) {
                UserAuthorizationCycle::create(['user_id' => $user->id, 'cycle_id' => $cycles[$data['cycle']]->id]);
            }

            $this->command?->info(($user->wasRecentlyCreated ? 'Creado' : 'Ya existía') . ": {$data['username']} → {$data['cycle']}");
        }
    }

    /** Ciclo con un único nivel, todavía sin aprobadores asignados. */
    private function cycle(string $name): AuthorizationCycle
    {
        $cycle = AuthorizationCycle::firstOrCreate(
            ['name' => $name],
            ['description' => "Aprobación de {$name}", 'is_active' => true]
        );

        AuthorizationCycleLevel::firstOrCreate(
            ['cycle_id' => $cycle->id, 'order' => 1],
            ['name' => $name]
        );

        return $cycle;
    }
}
