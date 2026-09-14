<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->string('dy_attachment_status', 20)
                ->nullable()
                ->index()
                ->after('dy_attachment_body');
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropIndex(['dy_attachment_status']);
            $table->dropColumn('dy_attachment_status');
        });
    }
};
