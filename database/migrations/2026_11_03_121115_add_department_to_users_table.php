<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors DY365's own TechnicianDepartment field (seen on both the live /dy365/technicians response and the
 * synced tech record) — saved locally so a supervisor's own department can be compared against it without
 * a live DY365 call every time. Also settable directly when a user is created/edited from the dashboard,
 * for techs that don't come from a DY365 sync at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('main_warehouse_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};
