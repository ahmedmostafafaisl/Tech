<?php
// ===== create_appointment_transactions_table =====

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('book_id')->unique()->comment('e.g. APP000010362');
            $table->unsignedBigInteger('rec_id')->comment('e.g. 5637266068');
            $table->unsignedBigInteger('tech_id')->comment('e.g. 5637256662');
            $table->timestamps();

            $table->index('book_id');
            $table->index('tech_id');
            $table->index('rec_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_transactions');
    }
};
