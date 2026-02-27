<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rental;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            RolesSeeder::class,
            ToolSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        User::factory()
            ->count(10)
            ->has(
                Rental::factory()
                    ->count(3)
                    ->hasPayments(2)
            )
            ->create();
    }
}
