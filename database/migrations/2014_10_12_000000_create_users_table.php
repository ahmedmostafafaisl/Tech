<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('tech_id')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->index(['type', 'phone'], 'idx_type_phone');
            $table->index(['type', 'username'], 'idx_type_username');
            $table->string('pin_code')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('type', ['tech', 'employee', 'customer'])->default('tech');
            $table->string('image')->nullable();
            $table->string('role')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('fcm_token')->nullable();
            // Additional fields for user management
            $table->string('warehouse_id')->nullable();
            $table->string('personnel_number')->nullable();
            $table->unsignedBigInteger('technician_rec_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('address')->nullable();
            $table->string('main_warehouse_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }



    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
