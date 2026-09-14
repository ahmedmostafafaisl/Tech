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
                $table->string('message_id')
                    ->nullable()
                    ->after('response');
            }

            if (!Schema::hasColumn('pre_appointment_messages', 'delivery_status')) {
                $table->string('delivery_status')
                    ->nullable()
                    ->after('message_id');
            }

            if (!Schema::hasColumn('pre_appointment_messages', 'delivery_error')) {
                $table->text('delivery_error')
                    ->nullable()
                    ->after('delivery_status');
            }
        });

        Schema::table('order_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('order_leads', 'q1_status')) {
                $table->string('q1_status')
                    ->nullable()
                    ->after('q1_message_id');
            }

            if (!Schema::hasColumn('order_leads', 'q2_status')) {
                $table->string('q2_status')
                    ->nullable()
                    ->after('q2_message_id');
            }

            if (!Schema::hasColumn('order_leads', 'q1_error')) {
                $table->text('q1_error')
                    ->nullable()
                    ->after('q1_status');
            }

            if (!Schema::hasColumn('order_leads', 'q2_error')) {
                $table->text('q2_error')
                    ->nullable()
                    ->after('q2_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            if (Schema::hasColumn('pre_appointment_messages', 'message_id')) {
                $table->dropColumn('message_id');
            }

            if (Schema::hasColumn('pre_appointment_messages', 'delivery_status')) {
                $table->dropColumn('delivery_status');
            }

            if (Schema::hasColumn('pre_appointment_messages', 'delivery_error')) {
                $table->dropColumn('delivery_error');
            }
        });

        Schema::table('order_leads', function (Blueprint $table) {
            if (Schema::hasColumn('order_leads', 'q1_status')) {
                $table->dropColumn('q1_status');
            }

            if (Schema::hasColumn('order_leads', 'q2_status')) {
                $table->dropColumn('q2_status');
            }

            if (Schema::hasColumn('order_leads', 'q1_error')) {
                $table->dropColumn('q1_error');
            }

            if (Schema::hasColumn('order_leads', 'q2_error')) {
                $table->dropColumn('q2_error');
            }
        });
    }
};
