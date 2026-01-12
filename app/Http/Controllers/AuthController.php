<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    const PREMIUM_PASSWORD_KEY = '!Dramajanuari2026';

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // If password matches the secret key, auto-subscribe for 1 year
        if ($request->password === self::PREMIUM_PASSWORD_KEY) {
            $user->subscriptions()->create([
                'plan_type' => 'monthly',
                'expires_at' => now()->addYear(),
                'payment_status' => 'success',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'is_subscribed' => ($request->password === self::PREMIUM_PASSWORD_KEY)
            ]),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // If password matches the secret key but they don't have an active subscription, give it to them
        if ($request->password === self::PREMIUM_PASSWORD_KEY) {
            $hasActive = $user->subscriptions()
                ->where('payment_status', 'success')
                ->where('expires_at', '>', now())
                ->exists();

            if (!$hasActive) {
                $user->subscriptions()->create([
                    'plan_type' => 'monthly',
                    'expires_at' => now()->addYear(),
                    'payment_status' => 'success',
                ]);
            }
        }

        // Check subscription status after potential update
        $isSubscribed = $user->subscriptions()
            ->where('payment_status', 'success')
            ->where('expires_at', '>', now())
            ->exists();

        // Hapus token lama agar hanya 1 device yang bisa login (Single Session)
        \Log::info("User login detected, deleting old tokens for: " . $user->email);
        Auth::logout(); // Membersihkan session lokal jika ada
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), ['is_subscribed' => $isSubscribed]),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // Hitung status premium secara real-time berdasarkan waktu sekarang
        $isSubscribed = $user->subscriptions()
            ->where('payment_status', 'success')
            ->where('expires_at', '>', now())
            ->exists();

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'is_subscribed' => (bool) $isSubscribed
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }
}
