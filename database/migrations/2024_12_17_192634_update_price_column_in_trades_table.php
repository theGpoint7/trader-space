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
        Schema::table('trades', function (Blueprint $table) {
            // Update 'price' to BIGINT or larger DECIMAL
            $table->decimal('price', 20, 8)->nullable()->change(); // Adjusted for large prices
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            // Revert 'price' to its original size
            $table->decimal('price', 16, 8)->nullable()->change();
        });
    }
};
