<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {

            $table->unsignedBigInteger('reason_rec_id')
                ->nullable()
                ->after('request_type');

            $table->string('reason')
                ->nullable()
                ->after('reason_rec_id');

            $table->index('reason_rec_id');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {

            $table->dropIndex(['reason_rec_id']);
            $table->dropColumn('reason_rec_id');
        });
    }
};
