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

    /**
     * Encrypt the API key before storing it.
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = encrypt($value);
    }

    /**
     * Decrypt the API key when accessing it.
     */
    public function getApiKeyAttribute($value)
    {
        return decrypt($value);
    }

    /**
     * Encrypt the API secret before storing it.
     */
    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = encrypt($value);
    }

    /**
     * Decrypt the API secret when accessing it.
     */
    public function getApiSecretAttribute($value)
    {
        return decrypt($value);
    }
}
