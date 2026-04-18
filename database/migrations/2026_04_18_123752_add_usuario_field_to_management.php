<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('management')->insert([
            'group'      => 'accountability_detail',
            'name'       => 'user_field',
            'label'      => 'Usuario',
            'value'      => '',
            'type'       => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('management')
            ->where('group', 'accountability_detail')
            ->where('name', 'user_field')
            ->delete();
    }
};
