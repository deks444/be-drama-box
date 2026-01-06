<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use Carbon\Carbon;

class PakasirController extends Controller
{
    public function webhook(Request $request)
    {
        // Pakasir sends webhook with this structure:
        // {
        //   "amount": 22000,
        //   "order_id": "240910HDE7C9",
        //   "project": "depodomain",
        //   "status": "completed",
        //   "payment_method": "qris",
        //   "completed_at": "2024-09-10T08:07:02.819+07:00"
        // }

        $orderId = $request->order_id;
        $status = $request->status;
        $amount = $request->amount;
        $project = $request->project;

        // Verify project matches
        if ($project !== config('services.pakasir.project')) {
            return response()->json(['message' => 'Invalid project'], 403);
        }

        $subscription = Subscription::where('order_id', $orderId)->first();

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        // Verify amount matches
        $prices = [
            'daily' => 3000,
            '3days' => 8000,
            'weekly' => 12000,
            'monthly' => 35000,
            'permanent' => 250000,
        ];
        $expectedAmount = $prices[$subscription->plan_type] ?? 0;

        if ($amount != $expectedAmount) {
            return response()->json(['message' => 'Amount mismatch'], 400);
        }

        if ($status === 'completed') {
            $this->activateSubscription($subscription);
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
    }
}
