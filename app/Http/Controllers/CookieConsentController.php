<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CookieConsentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'consent' => 'required|in:accepted,rejected',
        ]);

        $user = auth()->user();

        $user->cookie_consent = $request->consent === 'accepted';
        $user->save();

        return response()->json(['success' => true]);
    }
}
