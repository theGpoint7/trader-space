<?php

namespace App\Http\Controllers;

use App\Models\BrokerApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Render the settings page with existing broker API information.
     */
    public function index()
    {
        $user = Auth::user();

        // Fetch the user's broker API keys
        $brokerApiKeys = BrokerApiKey::where('user_id', $user->id)->get();

        // Pass decrypted API keys to the frontend
        return inertia('Settings', [
            'brokerApiKeys' => $brokerApiKeys->map(function ($key) {
                return [
                    'broker_name' => $key->broker_name,
                    'api_key' => decrypt($key->api_key), // Decrypt the API key
                    'api_secret' => decrypt($key->api_secret), // Decrypt the API secret
                ];
            }),
        ]);
    }

    /**
     * Save or update the broker API information.
     */
    public function saveBroker(Request $request)
    {
        // Validate the request
        $request->validate([
            'broker' => 'required|string',
            'apiKey' => 'required|string',
            'apiSecret' => 'required|string',
        ]);

        // Update or create the broker API information
        BrokerApiKey::updateOrCreate(
            [
                'user_id' => $request->user()->id, // Match on user and broker
                'broker_name' => $request->broker,
            ],
            [
                'api_key' => encrypt($request->apiKey), // Save encrypted API key
                'api_secret' => encrypt($request->apiSecret), // Save encrypted API secret
            ]
        );

        return redirect()->back()->with('success', 'Broker API information saved successfully.');
    }
}
