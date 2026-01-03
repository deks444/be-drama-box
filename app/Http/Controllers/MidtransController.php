<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class MidtransController extends Controller
{
    public function webhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $subscription = Subscription::where('order_id', $request->order_id)->first();

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $transactionStatus = $request->transaction_status;
        $type = $request->payment_type;
        $fraud = $request->fraud_status;

        if ($transactionStatus == 'capture') {
            if ($fraud == 'challenge') {
                $subscription->update(['payment_status' => 'pending']);
            } else if ($fraud == 'accept') {
                $this->activateSubscription($subscription);
            }
        } else if ($transactionStatus == 'settlement') {
            $this->activateSubscription($subscription);
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $subscription->update(['payment_status' => 'failed']);
        } else if ($transactionStatus == 'pending') {
            $subscription->update(['payment_status' => 'pending']);
        }

        return response()->json(['success' => true]);
    }

    private function activateSubscription($subscription)
    {
        $expiresAt = now();
        switch ($subscription->plan_type) {
            case 'daily':
                $expiresAt = now()->addDay();
                break;
            case '3days':
                $expiresAt = now()->addDays(3);
                break;
            case 'weekly':
                $expiresAt = now()->addWeek();
                break;
            case 'monthly':
                $expiresAt = now()->addMonth();
                break;
            case 'permanent':
                $expiresAt = now()->addYears(100);
                break;
            default:
                $expiresAt = now()->addDay();
        }

        $subscription->update([
            'payment_status' => 'success',
            'expires_at' => $expiresAt
        ]);

        // You could also trigger a notification or event here
    }
}
