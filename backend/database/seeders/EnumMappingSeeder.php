<?php

namespace Database\Seeders;

use App\Models\EnumMapping;
use Illuminate\Database\Seeder;

class EnumMappingSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            ['group' => 'ticket_status', 'key' => 'open', 'value' => 'Open'],
            ['group' => 'ticket_status', 'key' => 'in_progress', 'value' => 'In Progress'],
            ['group' => 'ticket_status', 'key' => 'closed', 'value' => 'Closed'],
        ];

        foreach ($mappings as $mapping) {
            EnumMapping::firstOrCreate([
                'group' => $mapping['group'],
                'key' => $mapping['key'],
            ], [
                'value' => $mapping['value'],
            ]);
        }
    }
}
