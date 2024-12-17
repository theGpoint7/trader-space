<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradesTable extends Migration
{
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // User making the trade
            $table->string('broker'); // e.g., "phemex"
            $table->string('order_id')->nullable(); // Broker's order ID
            $table->string('symbol'); // Trade symbol (e.g., BTCUSD)
            $table->enum('side', ['buy', 'sell']); // Buy or sell
            $table->decimal('quantity', 16, 8); // Amount traded
            $table->decimal('price', 16, 8)->nullable(); // Price per unit
            $table->decimal('leverage', 5, 2)->nullable(); // Leverage used
            $table->enum('status', ['open', 'closed'])->default('open'); // Open or closed
            $table->string('trigger_source')->nullable(); // e.g., "website_button", "signal"
            $table->unsignedBigInteger('signal_id')->nullable(); // ID of triggering signal (if any)
            $table->timestamps();

            // Foreign key for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Commented out foreign key for signal_id
            // $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
