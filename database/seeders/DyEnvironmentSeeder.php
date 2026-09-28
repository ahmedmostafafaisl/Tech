<?php

namespace Database\Seeders;

use App\Models\DyEnvironment;
use Illuminate\Database\Seeder;

class DyEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $environments = [
            ['name' => 'Production',  'url' => 'https://hamat-prod.operations.eu.dynamics.com',                    'is_default' => true],
            ['name' => 'UAT',         'url' => 'https://hamat-uat.sandbox.operations.eu.dynamics.com',              'is_default' => false],
            ['name' => 'UAT 02',      'url' => 'https://hamat-uat02.sandbox.operations.eu.dynamics.com',            'is_default' => false],
            ['name' => 'Dev 5',       'url' => 'https://naqi-dev05d11a9e2701c26003devaos.axcloud.dynamics.com',     'is_default' => false],
            ['name' => 'Dev 6',       'url' => 'https://naqi-dev0614ec34becbf5112bdevaos.axcloud.dynamics.com',     'is_default' => false],
            ['name' => 'Dev 7',       'url' => 'https://naqi-dev07e0d2be09243f5188devaos.axcloud.dynamics.com',     'is_default' => false],
            ['name' => 'Dev 10',       'url' => 'https://naqi-dev10f17f23242541dcafdevaos.axcloud.dynamics.com',     'is_default' => false],
        ];

        foreach ($environments as $env) {
            DyEnvironment::firstOrCreate(['url' => $env['url']], $env);
        }
    }
}
