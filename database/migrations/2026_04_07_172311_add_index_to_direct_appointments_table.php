<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up(): void
    {
        $indexExists = collect(DB::select("
        SHOW INDEX FROM direct_appointments
        WHERE Key_name = 'direct_appointments_tech_id_complete_v2_calling_index'
    "))->isNotEmpty();

        if (!$indexExists) {
            DB::statement("
            ALTER TABLE direct_appointments
            ADD INDEX direct_appointments_tech_id_complete_v2_calling_index
            (tech_id, complete_v2_calling(20))
        ");
        }
    }

    public function down(): void
    {
        $indexExists = collect(DB::select("
        SHOW INDEX FROM direct_appointments
        WHERE Key_name = 'direct_appointments_tech_id_complete_v2_calling_index'
    "))->isNotEmpty();

        if ($indexExists) {
            DB::statement("
            ALTER TABLE direct_appointments
            DROP INDEX direct_appointments_tech_id_complete_v2_calling_index
        ");
        }
    }
};
