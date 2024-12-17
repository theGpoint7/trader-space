<?php

namespace App\Services\Brokers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PhemexService
{
    protected $apiKey;
    protected $apiSecret;
    protected $http;

    const PLACE_ORDER_PATH = '/g-orders/create';

    public function __construct()
        {
            // Fetch keys from configuration (env variables)
            $this->apiKey = Config::get('services.phemex.api_key');
            $this->apiSecret = Config::get('services.phemex.api_secret');

            // Initialize HTTP client with base URI
            $this->http = new Client(['base_uri' => 'https://api.phemex.com']);

            // Log initialization
            Log::info('PhemexService initialized.', [
                'api_key' => $this->apiKey ? 'SET' : 'NOT SET',
                'api_secret' => $this->apiSecret ? 'SET' : 'NOT SET',
            ]);
        }

    public function placeOrder(array $orderDetails)
        {
            try {
                // Calculate expiry (1 minute from now)
                $expiry = now()->timestamp + 60;
        
                // Manually construct the query string to ensure parameter order matches Postman
                $queryString = 'clOrdID=' . $orderDetails['clOrdID'] .
                    '&side=' . $orderDetails['side'] .
                    '&ordType=' . $orderDetails['ordType'] .
                    '&timeInForce=' . $orderDetails['timeInForce'] .
                    '&symbol=' . $orderDetails['symbol'] .
                    '&posSide=' . $orderDetails['posSide'] .
                    '&orderQtyRq=' . $orderDetails['orderQtyRq'];
        
                // Construct the string to sign (remove '?' between path and query string)
                $stringToSign = self::PLACE_ORDER_PATH . $queryString . $expiry;
        
                // Generate the signature
                $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);
        
                // Log details for debugging
                \Log::info('Placing Order', [
                    'endpoint' => self::PLACE_ORDER_PATH,
                    'query_string' => $queryString,
                    'string_to_sign' => $stringToSign,
                    'signature' => $signature,
                    'expiry' => $expiry,
                ]);
        
                // Make the PUT request
                $response = $this->http->put(self::PLACE_ORDER_PATH, [
                    'query' => [
                        'clOrdID' => $orderDetails['clOrdID'],
                        'side' => $orderDetails['side'],
                        'ordType' => $orderDetails['ordType'],
                        'timeInForce' => $orderDetails['timeInForce'],
                        'symbol' => $orderDetails['symbol'],
                        'posSide' => $orderDetails['posSide'],
                        'orderQtyRq' => $orderDetails['orderQtyRq'],
                    ],
                    'headers' => [
                        'x-phemex-access-token' => $this->apiKey,
                        'x-phemex-request-expiry' => $expiry,
                        'x-phemex-request-signature' => $signature,
                        'Content-Type' => 'application/json',
                    ],
                ]);
        
                $responseBody = (string) $response->getBody();
        
                // Log the response
                \Log::info('Order Response', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody,
                ]);
        
                return json_decode($responseBody, true);
            } catch (\Exception $e) {
                // Log exception
                \Log::error('Order Placement Failed', [
                    'error_message' => $e->getMessage(),
                ]);
        
                return ['error' => $e->getMessage()];
            }
        }
        
    public function getPositions()
        {
            try {
                $expiry = now()->timestamp + 60; // 1-minute expiry
                $path = '/g-accounts/accountPositions';
                $queryString = 'currency=USDT';

                // Construct the string to sign (no '?' after the path)
                $stringToSign = $path . $queryString . $expiry;

                // Generate HMAC SHA256 signature
                $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

                // Log details for debugging
                \Log::info('Fetching Positions', [
                    'endpoint' => $path,
                    'string_to_sign' => $stringToSign,
                    'signature' => $signature,
                    'expiry' => $expiry,
                ]);

                // Make the GET request
                $response = $this->http->get($path, [
                    'query' => ['currency' => 'USDT'], // Pass the query parameter
                    'headers' => [
                        'x-phemex-access-token' => $this->apiKey,
                        'x-phemex-request-expiry' => $expiry,
                        'x-phemex-request-signature' => $signature,
                        'Content-Type' => 'application/json',
                    ],
                ]);

                $responseData = json_decode((string) $response->getBody(), true);

                // Log the raw response for debugging
                \Log::info('Phemex API Response:', $responseData);

                return $responseData;
            } catch (\Exception $e) {
                // Log the error message
                \Log::error('Phemex API Error: ' . $e->getMessage());
                return ['error' => $e->getMessage()];
            }
        }
    
    public function syncTradeHistory()
        {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->getTradeHistory();
        
            if (isset($response['error'])) {
                \Log::error('Error fetching trade history: ' . $response['error']);
                return;
            }
        
            $trades = $response['data']['rows'] ?? [];
        
            foreach ($trades as $trade) {
                // Find the trade by order_id or create it
                $existingTrade = Trade::firstOrCreate(
                    ['order_id' => $trade['orderID']],
                    [
                        'user_id' => auth()->id(),
                        'broker' => 'Phemex',
                        'cl_order_id' => $trade['clOrdID'],
                        'symbol' => $trade['symbol'],
                        'side' => strtolower($trade['side']),
                        'quantity' => $trade['execQtyRq'],
                        'price' => $trade['execPriceRp'],
                        'leverage' => $trade['leverageRr'] ?? null,
                        'status' => $trade['closedSizeRq'] > 0 ? 'closed' : 'open',
                    ]
                );
        
                // Log the trade event
                PositionLog::create([
                    'trade_id' => $existingTrade->id,
                    'order_id' => $trade['orderID'],
                    'cl_order_id' => $trade['clOrdID'],
                    'symbol' => $trade['symbol'],
                    'action' => $trade['side'],
                    'details' => json_encode($trade),
                    'executed_at' => Carbon::createFromTimestampNano($trade['transactTimeNs']),
                ]);
            }
        }
        
    public function syncPositions()
        {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->getPositions();
        
            if (isset($response['error'])) {
                \Log::error('Error fetching positions: ' . $response['error']);
                return;
            }
        
            $positions = $response['data']['positions'] ?? [];
        
            foreach ($positions as $position) {
                $existingTrade = Trade::where('symbol', $position['symbol'])
                    ->where('status', 'open')
                    ->first();
        
                if ($position['positionMarginRv'] > 0) {
                    if (!$existingTrade) {
                        // Create a new trade
                        $newTrade = Trade::create([
                            'user_id' => auth()->id(),
                            'broker' => 'Phemex',
                            'order_id' => null, // Assign when retrieved from order history
                            'symbol' => $position['symbol'],
                            'side' => $position['posSide'] === 'Buy' ? 'buy' : 'sell',
                            'quantity' => $position['size'],
                            'price' => $position['avgEntryPriceRp'],
                            'leverage' => $position['leverageRr'],
                            'status' => 'open',
                            'trigger_source' => 'broker',
                        ]);
        
                        // Log the creation
                        PositionLog::create([
                            'trade_id' => $newTrade->id,
                            'symbol' => $position['symbol'],
                            'action' => 'create',
                            'details' => json_encode($position),
                            'executed_at' => now(),
                        ]);
                    } else {
                        // Update the existing trade
                        $existingTrade->update([
                            'quantity' => $position['size'],
                            'leverage' => $position['leverageRr'],
                        ]);
        
                        // Log the update
                        PositionLog::create([
                            'trade_id' => $existingTrade->id,
                            'symbol' => $position['symbol'],
                            'action' => 'update',
                            'details' => json_encode($position),
                            'executed_at' => now(),
                        ]);
                    }
                } elseif ($position['positionMarginRv'] == 0 && $existingTrade) {
                    // Position has been closed
                    $existingTrade->update([
                        'status' => 'closed',
                    ]);
        
                    // Log the closure
                    PositionLog::create([
                        'trade_id' => $existingTrade->id,
                        'symbol' => $position['symbol'],
                        'action' => 'close',
                        'details' => json_encode($position),
                        'executed_at' => now(),
                    ]);
                }
            }
        }
        
    }
