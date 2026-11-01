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
        if (! Schema::hasColumn('direct_appointments', 'related_book_id')) {
            Schema::table('direct_appointments', function (Blueprint $table) {
                $table->string('related_book_id')->nullable()->after('book_id');
                $table->index('related_book_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('direct_appointments', 'related_book_id')) {
            Schema::table('direct_appointments', function (Blueprint $table) {
                $table->dropColumn('related_book_id');
                $table->dropIndex(['related_book_id']);
            });
        }
    }
};
