<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum values
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY technician_status
            ENUM('Confirmed', 'Rejected', 'Drafted', 'Draft')
            DEFAULT 'Drafted'
        ");

        // Make warehouse_id nullable
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY warehouse_id BIGINT UNSIGNED NULL
        ");

        DB::statement("
            ALTER TABLE transfer_order_lines
            MODIFY item_rec_id BIGINT UNSIGNED NULL
        ");
    }

    public function down(): void
    {
        // Revert enum
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY technician_status
            ENUM('Confirmed', 'Rejected', 'Drafted')
            DEFAULT 'Drafted'
        ");

        // Revert warehouse_id to NOT NULL
        DB::statement("
            ALTER TABLE transfer_orders
            MODIFY warehouse_id BIGINT UNSIGNED NOT NULL
        ");
    }
};
