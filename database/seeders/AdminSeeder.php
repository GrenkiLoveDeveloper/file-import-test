<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {

        User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('123'),
        ]);
    }
}
