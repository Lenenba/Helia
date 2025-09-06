<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'SuperAdmin',
            'email' => 'test@example.com',
        ]);

        //create additional users if needed
        User::factory()->count(5)->create();
    }
}
