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
        Schema::create('complete_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('sales_order_id')->nullable();
            $table->string('book_id')->nullable();
            $table->text('service_name')->nullable();  // الخدمة المقدمة
            $table->text('notes')->nullable();          // ملاحظات عامة
            $table->integer('home_salt')->nullable(); // ملح المنزل
            $table->string('home_salt_image')->nullable(); // صورة ملح المنزل
            $table->integer('device_salt')->nullable();  // ملح الجهاز
            $table->string('device_salt_image')->nullable(); // صورة ملح الجهاز
            $table->boolean('carbon_depletion')->default(false); // استنزاف الكربون
            $table->boolean('sink_cleaning')->default(false); // تنظيف الحوض
            $table->boolean('drain_connection')->default(false); // توصيل الصرف
            $table->string('carbon_depletion_image')->nullable(); // صورة تفريغ الكربون
            $table->string('sink_cleaning_image')->nullable(); // صورة تنظيف المجلي
            $table->string('drain_connection_image')->nullable(); // صورة توصيل الصرف
            $table->string('additional_image')->nullable();   // صورة إضافية
            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            // indexes
            $table->index('appointment_id');
            $table->index('book_id');
            $table->index('sales_order_id');
            // optional if you query by both together a lot
            $table->index(['sales_order_id', 'book_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complete_forms');
    }
};
