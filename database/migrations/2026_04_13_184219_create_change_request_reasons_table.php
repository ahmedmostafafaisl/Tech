<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_reasons', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('reason_rec_id')->unique();
            $table->string('reason_type');
            $table->string('reason');
            $table->string('title_ar')->default('اضف ملاحظاتك هنا');
            $table->string('title_en')->default('Add your notes here');
            $table->timestamps();
            // ✅ indexes
            $table->index('reason_rec_id');
            $table->index('reason_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_reasons');
    }
};
