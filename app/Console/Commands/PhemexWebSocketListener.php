<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PhemexWebSocketListener extends Command
{
    protected $signature = 'phemex:listen-websocket';
    protected $description = 'Listen to Phemex WebSocket for real-time BTC price updates';

    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = Config::get('services.phemex.api_key');
        $this->apiSecret = Config::get('services.phemex.api_secret');
    }

    public function handle()
    {
        $loop = LoopFactory::create();
        $connector = new Connector($loop);

        $connector('wss://ws.phemex.com')->then(function ($conn) use ($loop) {
            $this->info("WebSocket connected successfully!");
            Log::channel('websocket')->debug("WebSocket connected successfully");

            // Authenticate with the Phemex WebSocket
            $expiry = now()->timestamp + 120;
            $stringToSign = $this->apiKey . $expiry;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

            $authPayload = [
                "method" => "user.auth",
                "params" => [
                    "API",
                    $this->apiKey,
                    $signature,
                    $expiry
                ],
                "id" => 1
            ];

            $this->info("Auth Payload: " . json_encode($authPayload));
            Log::channel('websocket')->debug("Sending authentication payload", $authPayload);
            $conn->send(json_encode($authPayload));

            // Handle incoming messages
            $conn->on('message', function ($msg) use ($conn) {
                Log::channel('websocket')->debug("Received WebSocket message", ['message' => $msg]);
                $this->processMessage($msg, $conn);
            });

            // Add heartbeat pings
            $loop->addPeriodicTimer(5, function () use ($conn) {
                $pingPayload = [
                    "id" => 3,
                    "method" => "server.ping",
                    "params" => []
                ];
                $conn->send(json_encode($pingPayload));
                $this->info("Sent: server.ping");
                Log::channel('websocket')->debug("Sent server.ping payload", $pingPayload);
            });

            $conn->on('close', function () {
                $this->warn("WebSocket connection closed.");
                Log::channel('websocket')->debug("WebSocket connection closed");
            });
        }, function ($e) {
            $this->error("Could not connect: {$e->getMessage()}");
            Log::channel('websocket')->debug("WebSocket connection failed", ['error' => $e->getMessage()]);
        });

        $loop->run();
    }

    /**
     * Process incoming WebSocket messages.
     */
    private function processMessage($msg, $conn)
    {
        $this->info("Raw Message: {$msg}");
        Log::channel('websocket')->debug("Processing raw message", ['message' => $msg]);
        $data = json_decode($msg, true);

        // Handle errors
        if (isset($data['error']) && $data['error'] !== null) {
            $this->error("Error: " . json_encode($data['error']));
            Log::channel('websocket')->debug("Error in message", ['error' => $data['error']]);
            return;
        }

        // Successful authentication
        if (isset($data['id']) && $data['id'] == 1 && $data['result']['status'] === 'success') {
            $this->info("Authentication successful. Subscribing to BTC price updates...");
            Log::channel('websocket')->debug("Authentication successful");

            // Subscribe to BTC price updates
            $btcPricePayload = [
                "method" => "tick.subscribe",
                "params" => [".BTC"],
                "id" => 4
            ];
            $conn->send(json_encode($btcPricePayload));
            $this->info("Sent: BTC Price Subscription");
            Log::channel('websocket')->debug("Sent BTC price subscription payload", $btcPricePayload);
        }

        // Handle BTC price updates
        if (isset($data['tick']) && isset($data['tick']['symbol']) && $data['tick']['symbol'] === ".BTC") {
            $price = $data['tick']['last'] / (10 ** $data['tick']['scale']);
            $this->info("BTC Price Update: $price");
            Log::channel('websocket')->info("BTC Price Update", ['price' => $price]);

            // Broadcast the price to connected clients or update as needed
            broadcast(new \App\Events\BtcPriceUpdated($price));
        }
    }
}
