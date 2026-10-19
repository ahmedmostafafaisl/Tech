<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dy_payment_links', function (Blueprint $table) {
            $table->string('customer_account')->nullable()->after('phone');
            $table->string('book_id')->nullable()->after('customer_account');
            $table->text('dy_body')->nullable()->after('book_id');
            $table->text('dy_response')->nullable()->after('dy_body');
        });
    }

    public function down(): void
    {
        Schema::table('dy_payment_links', function (Blueprint $table) {
            $table->dropColumn(['customer_account', 'book_id', 'dy_body', 'dy_response']);
        });
    }
};
