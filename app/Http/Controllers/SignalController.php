<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Signal;

class SignalController extends Controller
{
    public function processSignal(Request $request)
{
    // Validate the incoming message
    $validated = $request->validate([
        'message' => 'required|string',
    ]);

    // Extract message
    $message = $validated['message'];

    // Parse the message (basic example using regular expressions)
    preg_match('/order (\w+) @ (\d+) filled on ([A-Za-z]+)\./', $message, $matches);

    if (!$matches) {
        return response()->json(['status' => 'error', 'message' => 'Invalid message format'], 400);
    }

    // Map the extracted data to fields
    $data = [
        'name' => 'Fair 2 Value Gap with Cooldown and Fast Downtrend Pause',
        'settings' => [
            'action' => $matches[1] ?? null, // buy/sell action
            'contracts' => $matches[2] ?? null, // number of contracts
            'ticker' => $matches[3] ?? null, // ticker (e.g., BTC)
        ],
        'status' => 'received',
        'received_at' => now(),
    ];

    // Store the signal in the database
    Signal::create($data);

    // Log the result
    \Log::info('Signal stored successfully:', $data);

    // Return a success response
    return response()->json(['status' => 'success', 'message' => 'Signal processed and stored successfully']);
}

}
