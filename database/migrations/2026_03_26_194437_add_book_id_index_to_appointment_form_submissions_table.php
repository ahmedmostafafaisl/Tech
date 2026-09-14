<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_form_submissions', function (Blueprint $table) {
            $table->index('book_id', 'afs_book_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_form_submissions', function (Blueprint $table) {
            $table->dropIndex('afs_book_id_idx');
        });
    }
};
