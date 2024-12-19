<?php

namespace App\Http\Controllers;

use App\Models\Trade;
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

        return Inertia::render('Trades', [
            'trades' => $trades,
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
                'posSide' => 'Long',
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

                // Fetch positions to retrieve leverage
                $positions = $phemexService->getPositions();
                $brokerPosition = collect($positions['data']['positions'] ?? [])
                    ->firstWhere('symbol', $validated['symbol']);

                if ($brokerPosition) {
                    $trade->update([
                        'leverage' => $brokerPosition['leverageRr'],
                    ]);
                }

                // Log the position creation
                PositionLog::create([
                    'trade_id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'action' => 'create',
                    'details' => json_encode($response['data']),
                    'executed_at' => now(),
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
     * Fetch positions and update trade status, including logging updates and closures.
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
    
            // Fetch all existing open trades for the user
            $openTrades = Trade::where('user_id', auth()->id())
                ->where('status', 'open')
                ->get();
    
            foreach ($positions as $position) {
                if ($position['size'] > 0) {
                    // Look for an existing trade with the same symbol
                    $existingTrade = Trade::where('user_id', auth()->id())
                        ->where('symbol', $position['symbol'])
                        ->where('status', 'open')
                        ->first();
    
                    if ($existingTrade) {
                        // Update the existing trade's details
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
                    } else {
                        // No existing trade, create a new one
                        $trade = Trade::create([
                            'user_id' => auth()->id(),
                            'broker' => 'Phemex',
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
                            'trade_id' => $trade->id,
                            'symbol' => $position['symbol'],
                            'action' => 'create',
                            'details' => json_encode($position),
                            'executed_at' => now(),
                        ]);
                    }
                } else {
                    // If position size is 0, mark the trade as closed
                    $closedTrade = Trade::where('user_id', auth()->id())
                        ->where('symbol', $position['symbol'])
                        ->where('status', 'open')
                        ->first();
    
                    if ($closedTrade) {
                        $closedTrade->update([
                            'status' => 'closed',
                            'updated_at' => now(),
                        ]);
    
                        // Log the closure
                        PositionLog::create([
                            'trade_id' => $closedTrade->id,
                            'symbol' => $position['symbol'],
                            'action' => 'close',
                            'details' => json_encode($position),
                            'executed_at' => now(),
                        ]);
                    }
                }
            }
    
            return inertia('Positions', [
                'positions' => $positions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception fetching positions: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function syncTradeHistory()
    {
        try {
            $phemexService = app('App\Services\Brokers\PhemexService');
            $response = $phemexService->getTradeHistory();
    
            // Log the raw API response for debugging
            \Log::info('Sync Trade History Response: ' . json_encode($response));
    
            if (isset($response['error'])) {
                \Log::error('Error fetching trade history: ' . $response['error']);
                return back()->withErrors(['error' => $response['error']]);
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
                    'executed_at' => now(),
                ]);
            }
    
            return back()->with('success', 'Trade history synced successfully.');
        } catch (\Exception $e) {
            \Log::error('Sync Trade History Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An unexpected error occurred.']);
        }
    }
    
}
