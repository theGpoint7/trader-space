<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignalForeignKeyToTradesTable extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropForeign(['signal_id']);
        });
    }
}
