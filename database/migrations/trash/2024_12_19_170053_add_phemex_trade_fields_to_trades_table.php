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
            // Add the new fields for storing Phemex trade data
            $table->string('transact_time_ns')->unique(); // Unique transaction time (nanoseconds)
            $table->string('exec_id')->nullable(); // Execution ID
            $table->enum('pos_side', ['Long', 'Short', 'None'])->nullable(); // Position side
            $table->enum('ord_type', ['Market', 'Limit', 'UNSPECIFIED'])->nullable(); // Order type
            $table->decimal('exec_qty', 16, 8)->nullable(); // Executed quantity
            $table->decimal('exec_value', 20, 8)->nullable(); // Executed value
            $table->decimal('exec_fee', 20, 8)->nullable(); // Execution fee
            $table->decimal('closed_pnl', 20, 8)->nullable(); // Closed profit/loss
            $table->decimal('fee_rate', 10, 8)->nullable(); // Fee rate
            $table->string('exec_status')->nullable(); // Execution status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            // Remove the fields added for Phemex trade data
            $table->dropColumn('transact_time_ns');
            $table->dropColumn('exec_id');
            $table->dropColumn('pos_side');
            $table->dropColumn('ord_type');
            $table->dropColumn('exec_qty');
            $table->dropColumn('exec_value');
            $table->dropColumn('exec_fee');
            $table->dropColumn('closed_pnl');
            $table->dropColumn('fee_rate');
            $table->dropColumn('exec_status');
        });
    }
};
