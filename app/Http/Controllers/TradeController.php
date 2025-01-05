<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\PhemexTrade;
use App\Models\PositionLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeController extends Controller
{
    /**
     * Display the list of trades for the authenticated user.
     */
        public function index()
    {
        $trades = Trade::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $phemexTrades = \App\Models\PhemexTrade::orderBy('transact_time_ns', 'desc')->get();

        return Inertia::render('Trades', [
            'trades' => $trades,
            'phemexTrades' => $phemexTrades,
        ]);
    }
    /**
     * Place a new order and log the position creation.
     */
        public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'clOrdID' => 'required|string',
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'posSide' => 'required|in:Long,Short', // Validate posSide
            'orderQtyRq' => 'required|numeric|min:0.001',
            'trigger_source' => 'nullable|string',
            'signal_id' => 'nullable|exists:signals,id',
        ]);
    
        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $orderDetails = [
                'clOrdID' => $validated['clOrdID'],
                'symbol' => $validated['symbol'],
                'side' => ucfirst($validated['side']),
                'ordType' => 'Market',
                'timeInForce' => 'ImmediateOrCancel',
                'posSide' => $validated['posSide'], // Pass posSide
                'orderQtyRq' => $validated['orderQtyRq'],
            ];
    
            $response = $phemexService->placeOrder($orderDetails);
    
            if (isset($response['error'])) {
                \Log::error('Trade Placement Error:', ['error' => $response['error']]);
                return response()->json(['error' => $response['error']], 500);
            }
    
            if ($response['code'] === 0 && $response['data']['ordStatus'] === 'Created') {
                // Create the trade
                $trade = Trade::create([
                    'user_id' => auth()->id(),
                    'broker' => 'Phemex',
                    'order_id' => $response['data']['orderID'],
                    'symbol' => $validated['symbol'],
                    'side' => $validated['side'],
                    'quantity' => $validated['orderQtyRq'],
                    'price' => $response['data']['priceRp'] ?? null,
                    'status' => 'open',
                    'trigger_source' => $validated['trigger_source'] ?? 'website_button',
                    'signal_id' => $validated['signal_id'] ?? null,
                ]);
    
                return response()->json(['success' => true, 'response' => $response]);
            }
    
            return response()->json(['error' => 'Trade could not be placed.'], 500);
        } catch (\Exception $e) {
            \Log::error('Trade Placement Failed:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    
    /**
     * Fetch positions and don't store them in the database.
     */
        public function getPositions()
    {
        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->getPositions();
    
            if (isset($response['error'])) {
                \Log::error('Error fetching positions: ' . $response['error']);
                return back()->withErrors(['error' => $response['error']]);
            }
    
            $positions = $response['data']['positions'] ?? [];
    
            // Return positions directly without updating trades
            return inertia('Positions', [
                'positions' => $positions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception fetching positions: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    
        public function fetchAccountBalance()
    {
        try {
                $phemexService = app('App\Services\Brokers\PhemexService');
                $balance = $phemexService->getAccountBalance();
        
                if (isset($balance['error'])) {
                    return response()->json(['error' => $balance['error']], 500);
                }
        
                return response()->json(['balance' => $balance]);
        } catch (\Exception $e) {
                \Log::error('Exception fetching account balance: ' . $e->getMessage());
                return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    
        public function syncTradeHistory()
    {
        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->getTradeHistory();
    
            // Log the response details
            \Log::info('Sync Trade History Response: ', $response);
    
            if (isset($response['error'])) {
                \Log::error('Error fetching trade history: ' . $response['error']);
                return response()->json(['error' => $response['error']], 409);
            }
    
            $trades = $response['data']['rows'] ?? [];
    
            if (empty($trades)) {
                \Log::warning('No trade history data returned from Phemex API.');
                return response()->json(['message' => 'No trades found in trade history.'], 200);
            }
    
            foreach ($trades as $trade) {
                try {
                    PhemexTrade::firstOrCreate(
                        ['transact_time_ns' => $trade['transactTimeNs'] ?? null],
                        [
                            'user_id' => auth()->id(),
                            'exec_id' => $trade['execID'] ?? null,
                            'pos_side' => $trade['posSide'] ?? null,
                            'ord_type' => $trade['ordType'] ?? null,
                            'exec_qty' => $trade['execQtyRq'] ?? null,
                            'exec_value' => $trade['execValueRv'] ?? null,
                            'exec_fee' => $trade['execFeeRv'] ?? null,
                            'closed_pnl' => $trade['closedPnlRv'] ?? null,
                            'fee_rate' => $trade['feeRateRr'] ?? null,
                            'exec_status' => $trade['execStatus'] ?? null,
                            'broker' => 'Phemex',
                            'symbol' => $trade['symbol'],
                            'side' => $trade['side'],
                            'price' => $trade['execPriceRp'] ?? null,
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error('Error saving trade: ' . $e->getMessage());
                }
            }
    
            return response()->json(['message' => 'Trade history synced successfully.'], 200);
        } catch (\Exception $e) {
            \Log::error('Sync Trade History Failed: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    

        public function changeLeverage(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'longLeverage' => 'required|integer|min:1|max:200',
            'shortLeverage' => 'required|integer|min:1|max:200',
        ]);

        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->changeLeverage(
                $validated['symbol'],
                $validated['longLeverage'],
                $validated['shortLeverage']
            );

            if (isset($response['error'])) {
                return response()->json(['error' => $response['error']], 500);
            }

            return response()->json(['success' => true, 'response' => $response]);
        } catch (\Exception $e) {
            \Log::error('Change Leverage Failed:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    
        public function showCurrentLeverage(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
        ]);
    
        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $positions = $phemexService->getPositions();
    
            $currentPosition = collect($positions['data']['positions'] ?? [])
                ->firstWhere('symbol', $validated['symbol']);
    
            if (!$currentPosition) {
                return response()->json(['error' => 'No position found for the symbol.'], 404);
            }
    
            return response()->json([
                'symbol' => $currentPosition['symbol'],
                'leverage' => $currentPosition['leverageRr'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Show Current Leverage Failed:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    




}
