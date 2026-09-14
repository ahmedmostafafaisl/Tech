<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        Announcement::insert([
            [
                'name' => 'System Maintenance',
                'subtitle' => 'Scheduled Downtime',
                'description' => 'The system will be down for maintenance on Saturday at 10 PM.',
                'date' => Carbon::now()->addDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'New Feature Release',
                'subtitle' => 'Inventory Enhancements',
                'description' => 'We have released a new feature for inventory management. Check it out!',
                'date' => Carbon::now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Holiday Notice',
                'subtitle' => 'Eid Vacation',
                'description' => 'The company will be closed for Eid holidays from June 10 to June 13.',
                'date' => Carbon::now()->addWeeks(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
