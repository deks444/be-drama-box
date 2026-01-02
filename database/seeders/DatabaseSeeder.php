<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Free User
        User::updateOrCreate(
            ['email' => 'free@example.com'],
            [
                'name' => 'Free User',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            ]
        );

        // Premium User
        $premiumUser = User::updateOrCreate(
            ['email' => 'premium@example.com'],
            [
                'name' => 'Premium User',
                'password' => \Illuminate\Support\Facades\Hash::make('!Dramajanuari2026'),
            ]
        );

        // Add subscription for premium user
        $premiumUser->subscriptions()->updateOrCreate(
            ['plan_type' => 'monthly'],
            [
                'expires_at' => now()->addYear(),
                'payment_status' => 'success',
            ]
        );
    }
}
