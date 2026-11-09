<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the one shared terminal used as a fallback for a tech with no terminal of their own (see
 * UserResource, default_terminal_active setting). Only one terminal is ever default at a time — enforced in
 * application code (NearPayTerminalController::setDefault()), not a DB constraint, since a partial unique
 * index on a boolean column isn't something a single Laravel migration can express the same way across both
 * SQLite and MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nearpay_terminals', function (Blueprint $table) {
            if (! Schema::hasColumn('nearpay_terminals', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('assigned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nearpay_terminals', function (Blueprint $table) {
            if (Schema::hasColumn('nearpay_terminals', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
