<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Hapus token lama
        $admin->tokens()->delete();

        // Buat token baru
        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->input('query');

        $users = User::where('email', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->with([
                'subscriptions' => function ($q) {
                    $q->where('payment_status', 'success')
                        ->where('expires_at', '>', now())
                        ->orderBy('expires_at', 'desc');
                }
            ])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function grantPremium(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_type' => 'required|string',
            'duration_days' => 'required|integer|min:1'
        ]);

        $user = User::findOrFail($request->user_id);

        // Buat subscription manual
        $expiresAt = now()->addDays($request->duration_days);

        $subscription = $user->subscriptions()->create([
            'plan_type' => $request->plan_type,
            'order_id' => 'MANUAL-' . time() . '-' . $user->id,
            'expires_at' => $expiresAt,
            'payment_status' => 'success',
        ]);

        \Log::info("Admin granted premium to user: {$user->email}, Plan: {$request->plan_type}, Duration: {$request->duration_days} days");

        return response()->json([
            'success' => true,
            'message' => 'Premium berhasil diberikan',
            'data' => $subscription
        ]);
    }

    public function getStats()
    {
        $totalUsers = User::count();
        $premiumUsers = User::whereHas('subscriptions', function ($q) {
            $q->where('payment_status', 'success')
                ->where('expires_at', '>', now());
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'premium_users' => $premiumUsers,
                'free_users' => $totalUsers - $premiumUsers
            ]
        ]);
    }
    public function getTransactions()
    {
        $transactions = \App\Models\Subscription::with('user:id,name,email')
            ->where('payment_status', 'success')
            ->orderBy('created_at', 'desc')
            ->get(); // Fetch all for now as requested "rekap", or simple pagination? Let's get 100 latest for simplicity or all? The request is "rekap". Let's limit to 50 for now or paginate. Actually, user asked for "menu rekap", usually implies a list. I'll use get() but maybe limit to 100 to avoid huge payload. Or just all. Since it's admin, they might want all.

        // Standard Practice: Use Pagination for APIs, but for "simple menu" requested by user without specifying pagination, fetch latest 100 is safer.
        // Actually, let's just return all successful ones.
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
