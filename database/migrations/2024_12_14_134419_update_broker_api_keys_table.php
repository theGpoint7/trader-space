<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBrokerApiKeysTable extends Migration
{
    public function up()
    {
        Schema::table('broker_api_keys', function (Blueprint $table) {
            $table->string('api_secret')->nullable()->after('api_key'); // Add api_secret column
        });
    }

    public function down()
    {
        Schema::table('broker_api_keys', function (Blueprint $table) {
            $table->dropColumn('api_secret');
        });
    }
}
