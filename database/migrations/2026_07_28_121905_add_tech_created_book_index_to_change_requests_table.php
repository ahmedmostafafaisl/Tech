<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->index(
                ['tech_id', 'created_at', 'book_id'],
                'idx_cr_tech_created_book'
            );
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropIndex('idx_cr_tech_created_book');
        });
    }
};
