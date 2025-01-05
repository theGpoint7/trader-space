<?php

namespace App\Services\Brokers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\BrokerApiKey;

class PhemexService
{
    protected $apiKey;
    protected $apiSecret;
    protected $http;

    const PLACE_ORDER_PATH = '/g-orders/create';

    public function __construct()
    {
        // Initialize HTTP client with base URI
        $this->http = new Client(['base_uri' => 'https://api.phemex.com']);

        // Fetch user-specific keys
        $user = Auth::user();
        if ($user) {
            $brokerKey = BrokerApiKey::where('user_id', $user->id)
                ->where('broker_name', 'Phemex')
                ->first();

            if ($brokerKey) {
                $this->apiKey = decrypt($brokerKey->api_key);
                $this->apiSecret = decrypt($brokerKey->api_secret);
            } else {
                Log::warning('No API keys found for the user.', ['user_id' => $user->id]);
            }
        } else {
            Log::warning('No authenticated user found. Falling back to default API keys.');
            $this->apiKey = config('services.phemex.api_key');
            $this->apiSecret = config('services.phemex.api_secret');
        }

        // Log initialization
        Log::info('PhemexService initialized.', [
            'api_key' => $this->apiKey ? 'SET' : 'NOT SET',
            'api_secret' => $this->apiSecret ? 'SET' : 'NOT SET',
        ]);
    }

    public function placeOrder(array $orderDetails)
    {
        try {
            $expiry = now()->timestamp + 60;
            $queryString = 'clOrdID=' . $orderDetails['clOrdID'] .
                '&side=' . $orderDetails['side'] .
                '&ordType=' . $orderDetails['ordType'] .
                '&timeInForce=' . $orderDetails['timeInForce'] .
                '&symbol=' . $orderDetails['symbol'] .
                '&posSide=' . $orderDetails['posSide'] .
                '&orderQtyRq=' . $orderDetails['orderQtyRq'];

            $stringToSign = self::PLACE_ORDER_PATH . $queryString . $expiry;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

            Log::info('Placing Order', [
                'endpoint' => self::PLACE_ORDER_PATH,
                'query_string' => $queryString,
                'string_to_sign' => $stringToSign,
                'signature' => $signature,
                'expiry' => $expiry,
            ]);

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
            Log::info('Order Response', [
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
            ]);

            return json_decode($responseBody, true);
        } catch (\Exception $e) {
            Log::error('Order Placement Failed', [
                'error_message' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    public function getPositions()
    {
        try {
            $expiry = now()->timestamp + 60;
            $path = '/g-accounts/accountPositions';
            $queryString = 'currency=USDT';
            $stringToSign = $path . $queryString . $expiry;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

            Log::info('Fetching Positions', [
                'endpoint' => $path,
                'string_to_sign' => $stringToSign,
                'signature' => $signature,
                'expiry' => $expiry,
            ]);

            $response = $this->http->get($path, [
                'query' => ['currency' => 'USDT'],
                'headers' => [
                    'x-phemex-access-token' => $this->apiKey,
                    'x-phemex-request-expiry' => $expiry,
                    'x-phemex-request-signature' => $signature,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseData = json_decode((string) $response->getBody(), true);
            Log::info('Phemex API Response:', $responseData);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Phemex API Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    public function syncTradeHistory()
        {
            try {
                $response = $this->getTradeHistory();
        
                // Log the raw API response
                \Log::info('Phemex API Response: ' . json_encode($response));
        
                if (isset($response['error'])) {
                    \Log::error('Error fetching trade history: ' . $response['error']);
                    return;
                }
        
                $trades = $response['data']['rows'] ?? [];
        
                foreach ($trades as $trade) {
                    $tradeData = [
                        'exec_id' => $trade['execID'],
                        'pos_side' => $trade['posSide'],
                        'ord_type' => $trade['ordType'],
                        'exec_qty' => $trade['execQtyRq'],
                        'exec_value' => $trade['execValueRv'],
                        'exec_fee' => $trade['execFeeRv'],
                        'closed_pnl' => $trade['closedPnlRv'],
                        'fee_rate' => $trade['feeRateRr'],
                        'exec_status' => $trade['execStatus'],
                        'broker' => 'Phemex',
                        'symbol' => $trade['symbol'],
                        'side' => $trade['side'],
                        'price' => $trade['execPriceRp'],
                    ];
        
                    // Conditionally include `transact_time_ns` if it exists
                    if (isset($trade['transactTimeNs'])) {
                        $tradeData['transact_time_ns'] = $trade['transactTimeNs'];
                    }
        
                    // Use the PhemexTrade model to insert or update trades in the `phemex_trades` table
                    $existingTrade = \App\Models\PhemexTrade::firstOrCreate(
                        ['transact_time_ns' => $trade['transactTimeNs'] ?? null],
                        $tradeData
                    );
        
                    // Log each trade synced
                    \Log::info('Synced Phemex Trade', [
                        'transact_time_ns' => $trade['transactTimeNs'] ?? 'NULL',
                        'symbol' => $trade['symbol'],
                        'side' => $trade['side'],
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error syncing trade history: ' . $e->getMessage());
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

    public function getTradeHistory()
        {
            try {
                $expiry = now()->timestamp + 60; // 1-minute expiry
                $path = '/api-data/g-futures/trades'; // Correct endpoint path
                $queryString = 'symbol=BTCUSDT'; // Correct query string for the endpoint
        
                // Construct the string to sign (path + queryString + expiry)
                $stringToSign = $path . $queryString . $expiry;
        
                // Generate HMAC SHA256 signature
                $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);
        
                // Log details for debugging
                \Log::info('Fetching Trade History', [
                    'endpoint' => $path,
                    'string_to_sign' => $stringToSign,
                    'signature' => $signature,
                    'expiry' => $expiry,
                ]);
        
                // Make the GET request
                $response = $this->http->get($path, [
                    'query' => ['symbol' => 'BTCUSDT'], // Pass the query parameter
                    'headers' => [
                        'x-phemex-access-token' => $this->apiKey,
                        'x-phemex-request-expiry' => $expiry,
                        'x-phemex-request-signature' => $signature,
                        'Content-Type' => 'application/json',
                    ],
                ]);
        
                $responseData = json_decode((string) $response->getBody(), true);
        
                // Log the raw response for debugging
                \Log::info('Trade History Response:', $responseData);
        
                return $responseData;
            } catch (\Exception $e) {
                // Log the error message
                \Log::error('Error fetching trade history: ' . $e->getMessage());
                return ['error' => $e->getMessage()];
            }
        }
        
    public function getAccountBalance()
        {
            try {
                $expiry = now()->timestamp + 60; // 1-minute expiry
                $path = '/g-accounts/accountPositions'; // Endpoint used for positions (includes account info)
                $queryString = 'currency=USDT'; // Adjust the query string as needed
        
                // Generate the signature
                $stringToSign = $path . $queryString . $expiry;
                $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);
        
                // Make the request
                $response = $this->http->get($path, [
                    'query' => ['currency' => 'USDT'],
                    'headers' => [
                        'x-phemex-access-token' => $this->apiKey,
                        'x-phemex-request-expiry' => $expiry,
                        'x-phemex-request-signature' => $signature,
                        'Content-Type' => 'application/json',
                    ],
                ]);
        
                $responseData = json_decode((string) $response->getBody(), true);
        
                if (isset($responseData['error'])) {
                    \Log::error('Error fetching account balance: ' . $responseData['error']);
                    return ['error' => $responseData['error']];
                }
        
                // Extract the account balance
                $account = $responseData['data']['account'] ?? null;
                return $account ? $account['accountBalanceRv'] : '0.0';
            } catch (\Exception $e) {
                \Log::error('Exception fetching account balance: ' . $e->getMessage());
                return ['error' => $e->getMessage()];
            }
        }
        
    public function changeLeverage(string $symbol, int $longLeverage, int $shortLeverage)
        {
            try {
                $expiry = now()->timestamp + 60; // 1-minute expiry
                $path = '/g-positions/leverage';
                $queryString = "symbol={$symbol}&longLeverageRr={$longLeverage}&shortLeverageRr={$shortLeverage}";
        
                // Construct the string to sign
                $stringToSign = $path . $queryString . $expiry;
        
                // Generate HMAC SHA256 signature
                $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);
        
                // Log details for debugging
                \Log::info('Changing Leverage', [
                    'endpoint' => $path,
                    'query_string' => $queryString,
                    'string_to_sign' => $stringToSign,
                    'signature' => $signature,
                    'expiry' => $expiry,
                ]);
        
                // Make the PUT request with query parameters
                $response = $this->http->put($path, [
                    'query' => [
                        'symbol' => $symbol,
                        'longLeverageRr' => $longLeverage,
                        'shortLeverageRr' => $shortLeverage,
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
                \Log::info('Leverage Change Response', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody,
                ]);
        
                return json_decode($responseBody, true);
            } catch (\Exception $e) {
                \Log::error('Leverage Change Failed', [
                    'error_message' => $e->getMessage(),
                ]);
        
                return ['error' => $e->getMessage()];
            }
        }
        
        

    }
