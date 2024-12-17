<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignalsTable extends Migration
{
    public function up()
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the signal
            $table->text('settings'); // JSON or text-based settings
            $table->enum('status', ['on', 'off', 'buy', 'sell'])->default('off'); // Current status of the signal
            $table->timestamp('received_at'); // Timestamp when the signal was received
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('signals');
    }
}
