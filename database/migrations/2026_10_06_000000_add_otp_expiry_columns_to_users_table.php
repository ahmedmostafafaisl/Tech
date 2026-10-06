<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patch A (auth): an OTP must expire and be single-use, and a successful OTP
 * check has to leave a short-lived server-side proof that the PIN step can rely
 * on. Both columns are nullable, so existing rows are untouched: an OTP that
 * already sits in the table has no expiry and is therefore treated as expired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'otp_verified_until')) {
                $table->timestamp('otp_verified_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['otp_expires_at', 'otp_verified_until'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
