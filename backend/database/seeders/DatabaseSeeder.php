<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user (apenas 1 admin para testes)
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create apenas 2 usuários regulares para testes (não 5!)
        $user1 = \App\Models\User::create([
            'name' => 'Test User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $user2 = \App\Models\User::create([
            'name' => 'Test User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create apenas alguns travel requests para testes (não muitos!)
        // 2 requests para user1
        \App\Models\TravelRequest::create([
            'user_id' => $user1->id,
            'requester_name' => $user1->name,
            'destination' => 'São Paulo, Brasil',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(37),
            'status' => 'requested',
            'notes' => 'Viagem de negócios para reunião com cliente',
        ]);

        \App\Models\TravelRequest::create([
            'user_id' => $user1->id,
            'requester_name' => $user1->name,
            'destination' => 'Rio de Janeiro, Brasil',
            'start_date' => now()->addDays(60),
            'end_date' => now()->addDays(67),
            'status' => 'approved',
            'approved_by' => $admin->id,
            'notes' => 'Viagem aprovada para conferência',
        ]);

        // 1 request para user2
        \App\Models\TravelRequest::create([
            'user_id' => $user2->id,
            'requester_name' => $user2->name,
            'destination' => 'Belo Horizonte, Brasil',
            'start_date' => now()->addDays(45),
            'end_date' => now()->addDays(52),
            'status' => 'requested',
            'notes' => 'Viagem para treinamento',
        ]);
    }
}
