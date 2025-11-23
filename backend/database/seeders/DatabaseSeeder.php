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
        // Create an admin user
        $admin = \App\Models\User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create regular users
        $users = \App\Models\User::factory(5)->create();

        // Create travel requests for each user
        foreach ($users as $user) {
            \App\Models\TravelRequest::factory(3)->create([
                'user_id' => $user->id,
            ]);
        }

        // Create some approved and cancelled requests
        \App\Models\TravelRequest::factory(2)->approved()->create([
            'user_id' => $users->first()->id,
        ]);

        \App\Models\TravelRequest::factory(1)->cancelled()->create([
            'user_id' => $users->last()->id,
        ]);
    }
}
