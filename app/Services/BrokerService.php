<?php

namespace App\Services;

use App\Services\Brokers\MexcService;

class BrokerService
{
    protected $brokers = [
        'phemex' => PhemexService::class, // Add other brokers here as needed
    ];

    public function placeOrder($brokerName, $apiKey, $apiSecret, $orderDetails)
    {
        if (!isset($this->brokers[$brokerName])) {
            throw new \Exception("Unsupported broker: $brokerName");
        }

        // Dynamically resolve the appropriate service class
        $brokerService = new $this->brokers[$brokerName]($apiKey, $apiSecret);

        return $brokerService->placeOrder($orderDetails);
    }
}
