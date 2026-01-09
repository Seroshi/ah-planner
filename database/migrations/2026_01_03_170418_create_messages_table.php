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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->integer('receiver_id')->nullable();
            $table->foreignId('workday_id')->nullable()->constrained();
            $table->date('date')->nullable();;
            $table->string('topic');
            $table->longText('remark');
            $table->longText('replier')->nullable();
            $table->longText('response')->nullable();
            $table->integer('status')->nullable();
            $table->boolean('read')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
