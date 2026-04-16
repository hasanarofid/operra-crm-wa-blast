<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required',
        ]);

        try {
            $user = $request->user();
            
            PushSubscription::updateOrCreate(
                ['endpoint' => $request->endpoint],
                [
                    'user_id' => $user->id,
                    'p256dh' => $request->keys['p256dh'],
                    'auth' => $request->keys['auth'],
                ]
            );

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Push Subscription Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function unsubscribe(Request $request)
    {
        $request->validate(['endpoint' => 'required']);
        
        PushSubscription::where('endpoint', $request->endpoint)->delete();
        
        return response()->json(['status' => 'success']);
    }
}
