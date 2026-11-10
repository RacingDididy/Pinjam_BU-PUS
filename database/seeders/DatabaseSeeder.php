<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@smkn5solo.sch.id'],
            [
                'name' => 'Admin Utama BU-PUS',
                'username' => 'adminbupus',
                'password' => Hash::make('Admin1234'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'profile_color' => 'indigo',
                'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            ]
        );

        User::updateOrCreate(
            ['email' => 'pustakawan@smkn5solo.sch.id'],
            [
                'name' => 'Petugas Pustakawan SMKN 5',
                'username' => 'pustakawan5',
                'password' => Hash::make('Pustakawan1234'),
                'role' => 'pustakawan',
                'is_active' => true,
                'email_verified_at' => now(),
                'profile_color' => 'emerald',
                'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            ]
        );

        $this->call([
            BukuSeeder::class,
            LaskarPelangiSeeder::class,
        ]);
    }
}
