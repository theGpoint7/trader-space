<?php

namespace App\Http\Controllers;

use App\Models\BrokerApiKey;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function saveBroker(Request $request)
    {
        $request->validate([
            'broker' => 'required|string',
            'apiKey' => 'required|string',
        ]);

        BrokerApiKey::updateOrCreate(
            ['user_id' => $request->user()->id, 'broker_name' => $request->broker],
            ['api_key' => $request->apiKey]
        );

        return redirect()->back()->with('success', 'Broker API information saved successfully.')->with('debug_props', session()->all());

    }
}
