<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Assurez-vous que l'utilisateur existe déjà

        Media::factory()
            ->avatar()
            ->forModel($user)
            ->create();

        Media::factory()
            ->gallery()
            ->count(9)
            ->forModel($user)
            ->create();
    }
}
