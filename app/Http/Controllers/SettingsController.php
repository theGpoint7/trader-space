<?php

namespace App\Http\Controllers;

use App\Models\BrokerApiKey;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return inertia('Settings'); // Renders the Settings page
    }

    public function saveBroker(Request $request)
    {
        // Validate both `apiKey` and `apiSecret`
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
