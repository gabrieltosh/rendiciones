<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            [
                'name'=>'Gabriel Pinto',
                'email'=>'gpinto.personal@gmail.com',
                'username'=>'gabriel.pinto',
                'type'=>'Administrador',
                'distribution_rule_one'=>NULL,
                'distribution_rule_second'=>NULL,
                'distribution_rule_three'=>NULL,
                'password'=>\Hash::make('12345'),
                'status'=>'Activo'
            ],
            [
                'name'=>'Marcos Flores',
                'email'=>'marcos.flores@gmail.com',
                'username'=>'marcos.flores',
                'type'=>'Administrador',
                'distribution_rule_one'=>NULL,
                'distribution_rule_second'=>NULL,
                'distribution_rule_three'=>NULL,
                'password'=>\Hash::make('12345'),
                'status'=>'Bloqueado'
            ]
        ]);
    }
}
