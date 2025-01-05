<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BrokerService;

class BrokerController extends Controller
{
    protected $brokerService;

    public function __construct(BrokerService $brokerService)
        {
            $this->brokerService = $brokerService;
        }

    public function placeOrder(Request $request)
        {
            // Validate generic order request parameters
            $request->validate([
                'broker_name' => 'required|string', // e.g., "mexc"
                'symbol' => 'required|string', // e.g., "BTC_USD"
                'side' => 'required|in:BUY,SELL', // Buy or Sell
                'type' => 'required|in:LIMIT,MARKET', // Order type
                'quantity' => 'required|numeric', // Quantity of asset
                'price' => 'nullable|numeric', // Price for LIMIT orders
                'leverage' => 'nullable|integer', // Optional leverage
                // Other generic fields can be added here as needed
            ]);

            $user = $request->user();
            $broker = $user->brokerApiKeys()->where('broker_name', $request->broker_name)->first();

            if (!$broker) {
                return response()->json(['error' => 'Broker API key not found for this user'], 404);
            }

            // Pass order details to the BrokerService
            try {
                $response = $this->brokerService->placeOrder(
                    $broker->broker_name, // The broker (e.g., "mexc")
                    $broker->api_key,
                    $broker->api_secret,
                    $request->all() // Pass all validated fields as-is
                );

                return response()->json($response);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

    public function saveBroker(Request $request)
        {
            // Validate incoming request
            $validatedData = $request->validate([
                'broker_name' => 'required|string',
                'apiKey' => 'required|string',
                'apiSecret' => 'required|string',
            ]);
    
            $user = Auth::user();
    
            // Save or update the broker API keys securely
            BrokerApiKey::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'broker_name' => $validatedData['broker_name'],
                ],
                [
                    'api_key' => $validatedData['apiKey'],
                    'api_secret' => $validatedData['apiSecret'],
                ]
            );
    
            return redirect()->back()->with('success', 'Broker API information saved securely!');
        }

}
