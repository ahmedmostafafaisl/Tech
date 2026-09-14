<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\AppointmentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RolesAndPermissionsSeeder::class,
            // TechUserAddressSeeder::class,
            // WarehouseSeeder::class,
            // CategorySeeder::class,
            // ItemSeeder::class,
            // PartSeeder::class,
            // UserStockSeeder::class,
            // UserStockItemSeeder::class,
            // UserStockPartSeeder::class,
            // AppointmentSeeder::class,
            // TaskSeeder::class,
            // WarehouseTransferSeeder::class,
            // AnnouncementSeeder::class,
            // EmergencyMainItemSeeder::class
            // AppointmentFormsSeeder::class,
            // PeriodicAppointmentsSeeder::class,
            // ServiceAppointmentsSeeder::class,
            // EmergencyAppointmentsSeeder::class,
            // SettingSeeder::class,
            // InvoiceSettingSeeder::class,
        ]);
    }
}
