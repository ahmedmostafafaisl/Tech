<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The appointment-completion OTP (AppointmentController::sendOtp/verifyOtp) had no expiry at all — a code
 * generated weeks ago would still verify today. This records when it was actually sent so verifyOtp can
 * reject a stale one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('complete_otp_sent_at')->nullable()->after('complete_otp');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('complete_otp_sent_at');
        });
    }
};
