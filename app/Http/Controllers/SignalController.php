<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class SignalController extends Controller
{
    public function index()
    {
        return Inertia::render('Signals');
    }
}
