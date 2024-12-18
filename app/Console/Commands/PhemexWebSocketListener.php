<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trade;
use App\Models\PositionLog;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class PhemexWebSocketListener extends Command
{
    protected $signature = 'phemex:listen-websocket';
    protected $description = 'Listen to Phemex WebSocket for updates';

    private $idCounter = 1;
    private $apiKey;
    private $apiSecret;

    public function __construct()
    {
        parent::__construct();

        $this->apiKey = Config::get('services.phemex.api_key');
        $this->apiSecret = Config::get('services.phemex.api_secret');

        if (!$this->apiKey || !$this->apiSecret) {
            throw new \RuntimeException('Phemex API Key or Secret is not configured.');
        }
    }

    private function nextId()
    {
        return $this->idCounter++;
    }

    public function handle()
    {
        $loop = LoopFactory::create();
        $connector = new Connector($loop);

        $connector('wss://ws.phemex.com')->then(function ($conn) use ($loop) {
            $this->info("WebSocket connected successfully!");

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
            $this->info("Sent: Authentication Request");

            $conn->on('message', function ($msg) use ($conn) {
                $data = json_decode($msg, true);
                $this->info("Received: " . json_encode($data));

                if (isset($data['id']) && $data['id'] == 1 && isset($data['result']) && $data['result'] === 'success') {
                    $this->info("Authentication successful. Subscribing to streams...");

                    $conn->send(json_encode([
                        "id" => $this->nextId(),
                        "method" => "aop.subscribe",
                        "params" => []
                    ]));
                    $this->info("Sent: AOP Subscription");

                    $conn->send(json_encode([
                        "id" => $this->nextId(),
                        "method" => "trade.subscribe",
                        "params" => ["BTCUSD"]
                    ]));
                    $this->info("Sent: Trade Subscription");
                }

                if (isset($data['error'])) {
                    $this->error("Error: " . json_encode($data['error']));
                }

                if (isset($data['trades'])) {
                    foreach ($data['trades'] as $trade) {
                        $this->processTrade($trade);
                    }
                }

                if (isset($data['positions'])) {
                    foreach ($data['positions'] as $position) {
                        $this->processPosition($position);
                    }
                }
            });

            $loop->addPeriodicTimer(5, function () use ($conn) {
                $pingPayload = ["id" => $this->nextId(), "method" => "server.ping", "params" => []];
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

    private function processTrade($trade)
    {
        [$timestampNs, $side, $priceEp, $quantity] = $trade;
        $timestampSec = floor($timestampNs / 1_000_000_000);
        $executedAt = Carbon::createFromTimestamp($timestampSec);
        $price = $priceEp / 10000;

        // Ensure the trade is recent
        if ($executedAt->diffInMinutes(now()) > 5) {
            $this->info("Ignored stale trade.");
            return;
        }

        $existingTrade = Trade::where('price', $price)
            ->where('quantity', $quantity)
            ->where('symbol', 'BTCUSD')
            ->first();

        if (!$existingTrade) {
            $tradeEntry = Trade::create([
                'user_id' => Config::get('services.phemex.user_id'),
                'broker' => 'Phemex',
                'order_id' => uniqid('trade-'),
                'symbol' => 'BTCUSD',
                'side' => strtolower($side),
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'executed',
                'trigger_source' => 'websocket'
            ]);

            PositionLog::create([
                'trade_id' => $tradeEntry->id,
                'symbol' => 'BTCUSD',
                'action' => 'create',
                'details' => json_encode($trade),
                'executed_at' => $executedAt
            ]);

            $this->info("Stored new trade: " . json_encode($trade));
        } else {
            $this->info("Duplicate trade ignored.");
        }
    }

    private function processPosition($position)
    {
        $symbol = $position['symbol'];
        $size = $position['size'];
        $price = $position['avgEntryPriceRp'] / 10000 ?? null;

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

                PositionLog::create([
                    'trade_id' => $trade->id,
                    'symbol' => $symbol,
                    'action' => 'create',
                    'details' => json_encode($position),
                    'executed_at' => now(),
                ]);

                $this->info("Stored new position: {$symbol}");
            }
        }
    }
}
