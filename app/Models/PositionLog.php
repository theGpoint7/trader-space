<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',      // Foreign key to the trade
        'symbol',        // Symbol like BTCUSDT
        'action',        // create, update, close
        'details',       // JSON column for additional details
        'executed_at',   // Timestamp of the event
    ];

    protected $casts = [
        'details' => 'array', // Automatically cast details to array
        'executed_at' => 'datetime', // Cast executed_at to Carbon instance
    ];

    // Relationship with Trade
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
