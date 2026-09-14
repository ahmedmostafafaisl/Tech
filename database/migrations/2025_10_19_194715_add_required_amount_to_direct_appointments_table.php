<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->decimal('required_amount', 10, 2)
                ->default(0)
                ->after('discount'); // add it after 'discount' for logical order
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down()
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropColumn('required_amount');
        });
    }
};
