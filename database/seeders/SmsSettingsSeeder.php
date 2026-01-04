<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $smsEvents = [
            ['event_type' => 'new', 'is_active' => true],
            ['event_type' => 'new-pix', 'is_active' => true],
            ['event_type' => 'pix-paid', 'is_active' => true],
            ['event_type' => 'new-withdraw', 'is_active' => true],
        ];

        foreach ($smsEvents as $event) {
            DB::table('sms_settings')->updateOrInsert(
                ['event_type' => $event['event_type']],
                ['is_active' => $event['is_active'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
