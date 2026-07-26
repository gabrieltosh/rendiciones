<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $params = [
        [
            'group' => 'accountability',
            'name' => 'export_mode',
            'label' => 'Modo de Exportación (SL / DI)',
            'value' => 'SL',
            'type' => 'text',
        ],
        [
            'group' => 'accountability',
            'name' => 'bridge_url',
            'label' => 'URL Servicio DI API',
            'value' => 'http://127.0.0.1:5001',
            'type' => 'text',
        ],
        [
            'group' => 'accountability',
            'name' => 'bridge_api_key',
            'label' => 'API Key Servicio DI API',
            'value' => '',
            'type' => 'password',
        ],
        [
            'group' => 'accountability',
            'name' => 'bridge_timeout',
            'label' => 'Timeout DI API (segundos)',
            'value' => '120',
            'type' => 'text',
        ],
    ];

    public function up(): void
    {
        foreach ($this->params as $param) {
            $exists = DB::table('management')
                ->where('group', $param['group'])
                ->where('name', $param['name'])
                ->exists();

            if (! $exists) {
                DB::table('management')->insert($param);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->params as $param) {
            DB::table('management')
                ->where('group', $param['group'])
                ->where('name', $param['name'])
                ->delete();
        }
    }
};
