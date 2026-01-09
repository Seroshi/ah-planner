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
        Schema::create('shift_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->integer('requester_id');
            $table->integer('receiver_id');
            $table->integer('workday_id_1');
            $table->integer('workday_id_2')->nullable();
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swaps');
    }
};
