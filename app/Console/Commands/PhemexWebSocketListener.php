<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trade;
use App\Models\PositionLog;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

            // Authenticate
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
            $conn->send(json_encode($authPayload));

            $conn->on('message', function ($msg) use ($conn) {
                $data = json_decode($msg, true);
                $this->info("Raw Message: {$msg}");
            
                // Handle authentication success
                if (isset($data['id']) && $data['id'] == 1 && $data['result']['status'] === 'success') {
                    $this->info("Authentication successful. Subscribing to trades, orders, and positions...");
            
                    // Subscribe to trades, orders, and positions for BTCUSD
                    $conn->send(json_encode([
                        "id" => 2,
                        "method" => "trade.subscribe",
                        "params" => ["BTCUSD"]
                    ]));
                    $this->info("Sent: trade.subscribe for BTCUSD");
            
                    $conn->send(json_encode([
                        "id" => 3,
                        "method" => "order.subscribe",
                        "params" => ["BTCUSD"]
                    ]));
                    $this->info("Sent: order.subscribe for BTCUSD");
            
                    $conn->send(json_encode([
                        "id" => 4,
                        "method" => "position.subscribe",
                        "params" => ["BTCUSD"]
                    ]));
                    $this->info("Sent: position.subscribe for BTCUSD");
                }
            
                // Process Orders
                if (isset($data['orders'])) {
                    $this->processOrders($data['orders']);
                }
            
                // Process Positions
                if (isset($data['positions'])) {
                    $this->processPositions($data['positions']);
                }
            
                // Process Trades
                if (isset($data['trades']) && isset($data['symbol'])) {
                    $this->processTrades($data['symbol'], $data['trades']);
                }
            });
            
            

            // Add Heartbeat Pings
            $loop->addPeriodicTimer(5, function () use ($conn) {
                $pingPayload = [
                    "id" => 99,
                    "method" => "server.ping",
                    "params" => []
                ];
                $conn->send(json_encode($pingPayload));
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
     * Process incoming orders.
     */
    private function processOrders($orders)
    {
        foreach ($orders as $order) {
            $orderId = $order['orderID'];
            $symbol = $order['symbol'];
            $side = strtolower($order['side']);
            $quantity = $order['orderQty'];
            $price = $order['priceEp'] / 10000 ?? null;
            $status = strtolower($order['ordStatus']);
            $executedAt = Carbon::createFromTimestampNano($order['transactTimeNs'] ?? now());

            // Check if trade already exists
            $existingTrade = Trade::where('order_id', $orderId)->first();

            if (!$existingTrade) {
                // Create new trade
                $trade = Trade::create([
                    'user_id' => 1, // Replace with dynamic user logic
                    'broker' => 'Phemex',
                    'order_id' => $orderId,
                    'symbol' => $symbol,
                    'side' => $side,
                    'quantity' => $quantity,
                    'price' => $price,
                    'status' => $status,
                    'trigger_source' => 'websocket',
                ]);

                PositionLog::create([
                    'trade_id' => $trade->id,
                    'symbol' => $symbol,
                    'action' => 'create',
                    'details' => json_encode($order),
                    'executed_at' => $executedAt,
                ]);

                $this->info("Order Created: {$orderId}, Symbol: {$symbol}");
            } else {
                $this->info("Order Already Exists: {$orderId}");
            }
        }
    }

    /**
     * Process incoming positions.
     */
    private function processPositions($positions)
    {
        foreach ($positions as $position) {
            $symbol = $position['symbol'];
            $size = $position['size'];
            $avgPrice = $position['avgEntryPriceRp'] / 10000 ?? null;

            $existingTrade = Trade::where('symbol', $symbol)
                ->where('status', 'open') // Look for open trades
                ->first();

            if ($size > 0) {
                if (!$existingTrade) {
                    // Create a new trade (open position)
                    $trade = Trade::create([
                        'user_id' => 1,
                        'broker' => 'Phemex',
                        'symbol' => $symbol,
                        'side' => strtolower($position['posSide']),
                        'quantity' => $size,
                        'price' => $avgPrice,
                        'status' => 'open',
                        'trigger_source' => 'websocket',
                    ]);

                    PositionLog::create([
                        'trade_id' => $trade->id,
                        'symbol' => $symbol,
                        'action' => 'create',
                        'details' => json_encode($position),
                        'executed_at' => now(),
                    ]);

                    $this->info("Position Opened: Symbol: {$symbol}, Status: open");
                } else {
                    // Update existing position
                    $existingTrade->update([
                        'quantity' => $size,
                        'price' => $avgPrice,
                    ]);

                    PositionLog::create([
                        'trade_id' => $existingTrade->id,
                        'symbol' => $symbol,
                        'action' => 'update',
                        'details' => json_encode($position),
                        'executed_at' => now(),
                    ]);

                    $this->info("Position Updated: Symbol: {$symbol}");
                }
            } elseif ($size == 0 && $existingTrade) {
                // Close the position
                $existingTrade->update(['status' => 'closed']);

                PositionLog::create([
                    'trade_id' => $existingTrade->id,
                    'symbol' => $symbol,
                    'action' => 'close',
                    'details' => json_encode($position),
                    'executed_at' => now(),
                ]);

                $this->info("Position Closed: Symbol: {$symbol}, Status: closed");
            }
        }
    }


    private function processTrades($symbol, $trades)
    {
        foreach ($trades as $trade) {
            [$timestampNs, $side, $priceEp, $quantity] = $trade;

            $timestampSec = floor($timestampNs / 1_000_000_000);
            $executedAt = Carbon::createFromTimestamp($timestampSec);
            $price = $priceEp / 10000;

            // Recency Check: Ignore trades older than 5 minutes
            if ($executedAt->diffInMinutes(now()) > 5) {
                continue;
            }

            $orderId = "trade-{$timestampNs}";

            // Check for duplicate trade
            $existingTrade = Trade::where('order_id', $orderId)
                ->where('price', $price)
                ->where('quantity', $quantity)
                ->first();

            if (!$existingTrade) {
                // Create new trade with status 'open'
                $tradeEntry = Trade::create([
                    'user_id' => 1, // Replace with dynamic user logic
                    'broker' => 'Phemex',
                    'order_id' => $orderId,
                    'symbol' => $symbol,
                    'side' => strtolower($side),
                    'quantity' => $quantity,
                    'price' => $price,
                    'status' => 'open', // Status remains open until explicitly closed
                    'trigger_source' => 'websocket',
                ]);

                PositionLog::create([
                    'trade_id' => $tradeEntry->id,
                    'symbol' => $symbol,
                    'action' => 'create',
                    'details' => json_encode($trade),
                    'executed_at' => $executedAt,
                ]);

                $this->info("Trade Created: Symbol: {$symbol}, Price: {$price}, Quantity: {$quantity}, Status: open");
            } else {
                $this->info("Duplicate Trade Ignored: Order ID: {$orderId}");
            }
        }
    }


}
