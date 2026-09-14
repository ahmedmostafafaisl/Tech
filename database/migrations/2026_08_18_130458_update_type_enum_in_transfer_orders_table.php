<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Temporarily allow BOTH old and new values
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY type ENUM(
                'TechnicianToWarehouse',
                'WarehouseToTechnician',
                'TechToTech',
                'TechnicianToTechnician'
            ) NOT NULL DEFAULT 'WarehouseToTechnician'
        ");

        // Step 2: Convert old values to the new value
        DB::table('transfer_orders')
            ->where('type', 'TechToTech')
            ->update([
                'type' => 'TechnicianToTechnician',
            ]);

        // Step 3: Remove the old value from the ENUM
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY type ENUM(
                'TechnicianToWarehouse',
                'WarehouseToTechnician',
                'TechnicianToTechnician'
            ) NOT NULL DEFAULT 'WarehouseToTechnician'
        ");
    }

    public function down(): void
    {
        // Temporarily allow both values
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY type ENUM(
                'TechnicianToWarehouse',
                'WarehouseToTechnician',
                'TechToTech',
                'TechnicianToTechnician'
            ) NOT NULL DEFAULT 'WarehouseToTechnician'
        ");

        // Convert new value back to old value
        DB::table('transfer_orders')
            ->where('type', 'TechnicianToTechnician')
            ->update([
                'type' => 'TechToTech',
            ]);

        // Restore original ENUM
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY type ENUM(
                'TechnicianToWarehouse',
                'WarehouseToTechnician',
                'TechToTech'
            ) NOT NULL DEFAULT 'WarehouseToTechnician'
        ");
    }
};
