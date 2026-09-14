<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_appointment_messages', 'message_id')) {
                $table->string('message_id')->nullable()->after('response');
            }

            if (!Schema::hasColumn('pre_appointment_messages', 'delivery_status')) {
                $table->string('delivery_status')->nullable()->after('message_id'); // sent/delivered/read/failed
            }

            if (!Schema::hasColumn('pre_appointment_messages', 'delivery_error')) {
                $table->text('delivery_error')->nullable()->after('delivery_status');
            }
        });

        // Index creation also needs its own existence check — adding it
        // again if it's already there throws a similar duplicate error.
        if (!$this->indexExists('pre_appointment_messages', 'pre_appointment_messages_message_id_index')) {
            Schema::table('pre_appointment_messages', function (Blueprint $table) {
                $table->index('message_id');
            });
        }

        Schema::table('order_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('order_leads', 'q1_status')) {
                $table->string('q1_status')->nullable()->after('q1_message_id');
            }

            if (!Schema::hasColumn('order_leads', 'q2_status')) {
                $table->string('q2_status')->nullable()->after('q2_message_id');
            }

            if (!Schema::hasColumn('order_leads', 'q1_error')) {
                $table->text('q1_error')->nullable()->after('q1_status');
            }

            if (!Schema::hasColumn('order_leads', 'q2_error')) {
                $table->text('q2_error')->nullable()->after('q2_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            $columns = array_filter(
                ['message_id', 'delivery_status', 'delivery_error'],
                fn($col) => Schema::hasColumn('pre_appointment_messages', $col)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('order_leads', function (Blueprint $table) {
            $columns = array_filter(
                ['q1_status', 'q2_status', 'q1_error', 'q2_error'],
                fn($col) => Schema::hasColumn('order_leads', $col)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Schema::hasColumn() has no equivalent for indexes, so we check
     * information_schema directly.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(1) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$dbName, $table, $indexName]
        );

        return ($result[0]->count ?? 0) > 0;
    }
};
