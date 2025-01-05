<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SignalService;

class SignalController extends Controller
{
    public function processSignal(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'order.strategy' => 'required|string',
            'order.action' => 'required|string',
            'order.price' => 'required|numeric',
            'order.ticker' => 'required|string',
            'order.type' => 'required|string',
        ]);

        // Extract the data
        $orderData = $validated['order'];

        

        // Process the signal (example: log it or call a service)
        // You can pass this data to a SignalService for further processing
        SignalService::process($orderData);

        // Log the signal for debugging purposes
        \Log::info('Received Signal:', $orderData);

        // Return a success response
        return response()->json(['status' => 'success', 'message' => 'Signal processed successfully']);
    }
}
