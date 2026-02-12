<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $preference = $user->preferences()->firstOrCreate(['user_id' => $user->id]);
        
        return response()->json($preference);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $preference = $user->preferences()->firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'theme' => 'nullable|in:light,dark,system',
            'font_size' => 'nullable|in:small,medium,large',
            'notifications_enabled' => 'boolean',
            'email_notifications' => 'boolean',
            'language' => 'nullable|string|size:2',
            'timezone' => 'nullable|string',
        ]);

        $preference->update($validated);

        return response()->json($preference);
    }
}
