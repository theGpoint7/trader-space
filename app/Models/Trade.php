<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'broker',
        'order_id',
        'symbol',
        'side',
        'quantity',
        'price',
        'leverage',
        'status',
        'trigger_source',
        'signal_id',
    ];

    // Define relationships

    /**
     * The user who created the trade.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The signal that triggered the trade (if any).
     */
    // public function signal()
    // {
    //     return $this->belongsTo(Signal::class);
    // }
}
