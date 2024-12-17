<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'settings',
        'status',
        'received_at',
    ];

    // Parse settings as JSON automatically
    protected $casts = [
        'settings' => 'array',
        'received_at' => 'datetime',
    ];
}
