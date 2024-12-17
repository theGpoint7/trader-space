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
        Schema::create('position_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id'); // Link to trades table
            $table->string('symbol'); // Symbol of the trade
            $table->string('action'); // create, update, close
            $table->json('details')->nullable(); // JSON column for extra details
            $table->timestamp('executed_at')->nullable(); // Timestamp of the event
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_logs');
    }
};
