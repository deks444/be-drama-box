<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = $request->user()->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    public function checkStatus($orderId)
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');

        try {
            $status = \Midtrans\Transaction::status($orderId);
            $subscription = Subscription::where('order_id', $orderId)->first();

            if (!$subscription) {
                return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
            }

            // Ensure status is treated as an object as returned by Midtrans SDK
            $transactionStatus = is_object($status) ? $status->transaction_status : $status['transaction_status'];

            if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
                // Activate subscription
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
                    default:
                        $expiresAt = now()->addDay();
                }

                $subscription->update([
                    'payment_status' => 'success',
                    'expires_at' => $expiresAt
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran berhasil dikonfirmasi!',
                    'status' => 'success'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status saat ini: ' . $transactionStatus,
                'status' => $transactionStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        $subscription = $request->user()->subscriptions()->findOrFail($id);

        if ($subscription->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya transaksi pending yang dapat dihapus.'
            ], 400);
        }

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibatalkan.'
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|string',
            'duration' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        $user = $request->user();

        // Set Midtrans Configuration
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $orderId = 'DRAMA-' . time() . '-' . $user->id;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'callbacks' => [
                'finish' => 'http://localhost:5173/membership?status=success',
            ]
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // Create pending subscription
            $subscription = $user->subscriptions()->create([
                'plan_type' => $request->plan_id,
                'order_id' => $orderId,
                'expires_at' => now(), // Will be updated on success webhook
                'payment_status' => 'pending',
                'snap_token' => $snapToken
            ]);

            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'order_id' => $orderId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
