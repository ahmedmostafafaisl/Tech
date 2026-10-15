<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A fast, indexed, permanent record of "this serial was confirmed sent to DY365 under this appointment" —
 * built specifically so singleAppointmentByBookId can check "is this serial already confirmed sent under a
 * DIFFERENT appointment" with one indexed query, instead of parsing dy_body JSON for every appointment on
 * every request.
 *
 * Populated once, at the exact moment CompleteSuccessPaymentsCommand confirms DY365 actually accepted the
 * payload (the same moment complete_flag gets set) — never re-derived from dy_body at read time, and never
 * expires. This is what makes it correct for the "appointment later flips to Cancelled/Deferral independently"
 * case: once a serial lands here, nothing that happens to the appointment's status afterward removes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmitted_serials', function (Blueprint $table) {
            $table->id();
            $table->string('book_id');
            $table->string('tech_id')->nullable();
            $table->string('item_number')->nullable();
            $table->string('serial');

            $table->timestamps();

            $table->unique(['book_id', 'serial']);
            $table->index(['item_number', 'serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmitted_serials');
    }
};
