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
        Schema::table('change_request_additional_images', function (Blueprint $table) {
            $table->index('change_request_id', 'crai_change_request_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('change_request_additional_images', function (Blueprint $table) {
            $table->dropIndex('crai_change_request_id_index');
        });
    }
};
