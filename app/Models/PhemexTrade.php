<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhemexTrade extends Model
{
    use HasFactory;

    protected $table = 'phemex_trades';

    protected $fillable = [
        'transact_time_ns',
        'exec_id',
        'pos_side',
        'ord_type',
        'exec_qty',
        'exec_value',
        'exec_fee',
        'closed_pnl',
        'fee_rate',
        'exec_status',
        'broker',
        'symbol',
        'side',
        'price',
    ];
}
