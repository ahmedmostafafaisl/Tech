<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('book_id')->nullable()->after('sales_order_id');

            if (!$this->indexExists('change_requests', 'idx_cr_book_id')) {
                $table->index('book_id', 'idx_cr_book_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            if ($this->indexExists('change_requests', 'idx_cr_book_id')) {
                $table->dropIndex('idx_cr_book_id');
            }

            $table->dropColumn('book_id');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
