<?php

namespace App\Services;

class SignalService
{
    public static function process(array $orderData)
    {
        // Example: Save the data to the database or trigger trading actions
        \Log::info('Processing signal in SignalService:', $orderData);

        // Save to database if needed
        // Signal::create($orderData);
    }
}
