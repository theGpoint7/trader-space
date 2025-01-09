<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PhemexWebSocketListener extends Command
{
    protected $signature = 'phemex:listen-websocket';
    protected $description = 'Listen to Phemex WebSocket for real-time BTC price updates';

    private $apiKey;
    private $apiSecret;

    public function handle()
    {
        // Fetch the API key and secret from the .env file
        $this->apiKey = env('PHEMEX_API_KEY');
        $this->apiSecret = env('PHEMEX_API_SECRET');

        if (!$this->apiKey || !$this->apiSecret) {
            $this->error('API key or secret not found in the environment variables.');
            return;
        }

        $loop = LoopFactory::create();
        $connector = new Connector($loop);

        $connector('wss://ws.phemex.com')->then(function ($conn) use ($loop) {
            $this->info("WebSocket connected successfully!");

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
                $data = json_decode($msg, true);
                if (isset($data['tick']['last'])) {
                    $btcPrice = $data['tick']['last'] / pow(10, $data['tick']['scale']);
                    Log::channel('websocket')->info("BTC Price Update: $btcPrice");

                    // Send the BTC price to the Socket.io server
                    $response = Http::post('http://localhost:4000/update-btc-price', [
                        'price' => $btcPrice
                    ]);

                    if ($response->successful()) {
                        Log::channel('websocket')->info("BTC Price sent to Socket.IO server successfully", [
                            'price' => $btcPrice
                        ]);
                    } else {
                        Log::channel('websocket')->error("Failed to send BTC price to Socket.IO server", [
                            'price' => $btcPrice,
                            'response' => $response->body()
                        ]);
                    }
                }
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
            });

            $conn->on('close', function () {
                $this->warn("WebSocket connection closed.");
            });
        }, function ($e) {
            $this->error("Could not connect: {$e->getMessage()}");
        });

        $loop->run();
    }

    /**
     * Process incoming WebSocket messages.
     */
    private function processMessage($msg, $conn)
    {
        $this->info("Raw Message: {$msg}");
        $data = json_decode($msg, true);

        // Handle errors
        if (isset($data['error']) && $data['error'] !== null) {
            $this->error("Error: " . json_encode($data['error']));
            return;
        }

        // Successful authentication
        if (isset($data['id']) && $data['id'] == 1 && $data['result']['status'] === 'success') {
            $this->info("Authentication successful. Subscribing to BTC price updates...");

            // Subscribe to BTC price updates
            $btcPricePayload = [
                "method" => "tick.subscribe",
                "params" => [".BTC"],
                "id" => 4
            ];
            $conn->send(json_encode($btcPricePayload));
            $this->info("Sent: BTC Price Subscription");
        }

        // Handle BTC price updates
        if (isset($data['tick']) && isset($data['tick']['symbol']) && $data['tick']['symbol'] === ".BTC") {
            $price = $data['tick']['last'] / (10 ** $data['tick']['scale']);
            $this->info("BTC Price Update: $price");

            // Send the BTC price to the Socket.io server
            $response = Http::post(env('SOCKETIO_HOST') . '/update-btc-price', [
                'price' => $price,
            ]);
            
            

            if ($response->successful()) {
                $this->info("BTC Price sent to Socket.IO server successfully");
            } else {
                $this->error("Failed to send BTC price to Socket.IO server");
            }
        }
    }
}
