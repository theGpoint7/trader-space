<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrokerApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'broker_name',
        'api_key',
        'api_secret',
    ];
}
