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
}
