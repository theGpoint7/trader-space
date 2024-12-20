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
        Schema::create('phemex_trades', function (Blueprint $table) {
            $table->id(); // Primary key

            // Nullable columns for flexibility
            $table->unsignedBigInteger('transact_time_ns')->nullable();
            $table->string('exec_id')->nullable();
            $table->enum('pos_side', ['Long', 'Short', 'None'])->nullable();
            $table->enum('ord_type', ['Market', 'Limit', 'UNSPECIFIED'])->nullable();
            $table->decimal('exec_qty', 16, 8)->nullable();
            $table->decimal('exec_value', 20, 8)->nullable();
            $table->decimal('exec_fee', 20, 8)->nullable();
            $table->decimal('closed_pnl', 20, 8)->nullable();
            $table->decimal('fee_rate', 10, 8)->nullable();
            $table->string('exec_status')->nullable();
            $table->string('broker')->default('Phemex');
            $table->string('symbol')->nullable();
            $table->enum('side', ['Buy', 'Sell'])->nullable();
            $table->decimal('price', 20, 8)->nullable();

            // Timestamps for record keeping
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phemex_trades');
    }
};
