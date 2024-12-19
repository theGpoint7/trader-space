<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trade;
use App\Models\PositionLog;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PhemexWebSocketListener extends Command
{
    protected $signature = 'phemex:listen-websocket';
    protected $description = 'Listen to Phemex WebSocket for real-time trade and position updates';

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

            // Step 1: Authenticate
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
            $this->info("Authentication successful. Subscribing to streams...");
            Log::channel('websocket')->debug("Authentication successful");

            // // Subscribe to trade updates
            // $tradePayload = [
            //     "method" => "trade.subscribe",
            //     "params" => ["BTCUSD"], // Replace with your trading symbol
            //     "id" => 4
            // ];
            // $conn->send(json_encode($tradePayload));
            // $this->info("Sent: Trade Subscription");
            // Log::channel('websocket')->debug("Sent trade subscription payload", $tradePayload);

            // Subscribe to AOP (account/order/position) updates
            $aopPayload = [
                "method" => "aop.subscribe",
                "params" => [],
                "id" => 2
            ];
            $conn->send(json_encode($aopPayload));
            $this->info("Sent: AOP Subscription");
            Log::channel('websocket')->debug("Sent AOP subscription payload", $aopPayload);
        }

        // Successful AOP subscription
        if (isset($data['id']) && $data['id'] == 2 && $data['error'] === null) {
            $this->info("AOP subscription successful.");
            Log::channel('websocket')->debug("AOP subscription successful");
        }

        // Successful trade subscription
        if (isset($data['id']) && $data['id'] == 4 && $data['error'] === null) {
            $this->info("Trade subscription successful.");
            Log::channel('websocket')->debug("Trade subscription successful");
        }

        // Handle incoming trade messages
        if (isset($data['trades'])) {
            foreach ($data['trades'] as $trade) {
                $this->info("Trade Executed: " . json_encode($trade));
                Log::channel('websocket')->debug("Trade executed", $trade);
            }
        }

        // Handle AOP updates
        if (isset($data['accounts']) || isset($data['orders']) || isset($data['positions'])) {
            $this->info("AOP Update: " . json_encode($data));
            Log::channel('websocket')->debug("AOP update received", $data);

            if (isset($data['positions'])) {
                foreach ($data['positions'] as $position) {
                    $this->updatePosition($position);
                }
            }
        }
    }

    private function updatePosition($position)
    {
        $symbol = $position['symbol'];
        $size = $position['size'];
        $price = $position['avgEntryPriceRp'] / 10000 ?? null;

        $this->info("Processing Position: Symbol: $symbol, Size: $size, Price: $price");
        Log::channel('websocket')->debug("Processing position", $position);

        $existingTrade = Trade::where('symbol', $symbol)->where('status', 'open')->first();

        if ($size > 0) {
            if (!$existingTrade) {
                $trade = Trade::create([
                    'user_id' => Config::get('services.phemex.user_id'),
                    'broker' => 'Phemex',
                    'symbol' => $symbol,
                    'side' => strtolower($position['side']),
                    'quantity' => $size,
                    'price' => $price,
                    'status' => 'open',
                    'trigger_source' => 'websocket',
                ]);
                $this->info("Stored New Position: " . json_encode($trade));
                Log::channel('websocket')->debug("Stored new position", $trade->toArray());
            } else {
                $this->info("Existing position updated.");
                Log::channel('websocket')->debug("Existing position updated", ['symbol' => $symbol]);
            }
        }
    }
}
