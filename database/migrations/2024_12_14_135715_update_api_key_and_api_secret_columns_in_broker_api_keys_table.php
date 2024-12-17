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
        Schema::table('broker_api_keys', function (Blueprint $table) {
            $table->text('api_key')->change();
            $table->text('api_secret')->change(); // Ensure this field is TEXT as well
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('broker_api_keys', function (Blueprint $table) {
        $table->string('api_key', 255)->nullable()->change(); // Ensure nullable
        $table->string('api_secret', 255)->nullable()->change(); // Ensure nullable
    });
}

};
