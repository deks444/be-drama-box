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
        try {
            $subscription = Subscription::where('order_id', $orderId)->first();
            if (!$subscription) {
                return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
            }

            // Call Pakasir Transaction Detail API
            $pakasirProject = config('services.pakasir.project');
            $pakasirApiKey = config('services.pakasir.api_key');
            $amount = $subscription->plan_type;

            // Get amount from plan_type
            $prices = [
                'daily' => 3000,
                '3days' => 8000,
                'weekly' => 12000,
                'monthly' => 35000,
                'permanent' => 250000,
            ];
            $amount = $prices[$subscription->plan_type] ?? 0;

            $url = "https://app.pakasir.com/api/transactiondetail?project={$pakasirProject}&amount={$amount}&order_id={$orderId}&api_key={$pakasirApiKey}";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check payment status'
                ], 500);
            }

            $pakasirResponse = json_decode($response, true);

            if (!isset($pakasirResponse['transaction'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            $transaction = $pakasirResponse['transaction'];
            $transactionStatus = $transaction['status'];

            if ($transactionStatus === 'completed') {
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
        ]);

        $user = $request->user();

        // Enforce Server-Side Pricing
        $prices = [
            'daily' => 3000,
            '3days' => 8000,
            'weekly' => 12000,
            'monthly' => 35000,
            'permanent' => 250000,
        ];

        if (!array_key_exists($request->plan_id, $prices)) {
            return response()->json(['success' => false, 'message' => 'Invalid plan type'], 400);
        }

        // Check if user has pending transaction
        $hasPending = $user->subscriptions()
            ->where('payment_status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki transaksi pending. Silakan selesaikan atau batalkan transaksi tersebut terlebih dahulu di Riwayat Transaksi.',
                'has_pending' => true
            ], 400);
        }

        $grossAmount = $prices[$request->plan_id];

        $orderId = 'DRAMA-' . time() . '-' . $user->id;

        // Pakasir API Configuration
        $pakasirProject = config('services.pakasir.project');
        $pakasirApiKey = config('services.pakasir.api_key');
        $pakasirUrl = 'https://app.pakasir.com/api/transactioncreate/qris';

        $payload = [
            'project' => $pakasirProject,
            'order_id' => $orderId,
            'amount' => $grossAmount,
            'api_key' => $pakasirApiKey
        ];

        try {
            // Call Pakasir API to create QRIS payment
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pakasirUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Pakasir API error: ' . $response);
            }

            $pakasirResponse = json_decode($response, true);

            if (!isset($pakasirResponse['payment'])) {
                throw new \Exception('Invalid response from Pakasir');
            }

            $paymentData = $pakasirResponse['payment'];

            // Create pending subscription
            $subscription = $user->subscriptions()->create([
                'plan_type' => $request->plan_id,
                'order_id' => $orderId,
                'expires_at' => now(), // Will be updated on success webhook
                'payment_status' => 'pending',
                'snap_token' => $paymentData['payment_number'] // Store QR string for later retrieval
            ]);

            return response()->json([
                'success' => true,
                'payment' => [
                    'qr_string' => $paymentData['payment_number'],
                    'amount' => $paymentData['amount'],
                    'fee' => $paymentData['fee'],
                    'total_payment' => $paymentData['total_payment'],
                    'expired_at' => $paymentData['expired_at'],
                    'order_id' => $orderId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
