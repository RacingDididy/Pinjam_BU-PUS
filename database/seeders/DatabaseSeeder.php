<?php

namespace Database\Seeders;

use App\Models\Kategori;
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

        $kategoriList = [
            'Pelajaran' => 'Buku paket sekolah, kamus, dan modul belajar',
            'Novel' => 'Karya sastra dan novel fiksi best seller',
            'Pengembangan Diri' => 'Buku motivasi, pengembangan diri, dan psikologi terapan',
            'Bisnis dan Ekonomi' => 'Buku finansial, investasi, dan bisnis praktis',
            'Komik' => 'Koleksi komik bergambar populer dan manga',
            'Agama' => 'Kajian sejarah, fiqih, dan literatur keagamaan',
            'Hukum dan Politik' => 'Kajian tata negara, hukum perundang-undangan',
            'Resep' => 'Aneka resep masakan nusantara dan internasional',
            'Sejarah dan Budaya' => 'Kajian sejarah peradaban, kebudayaan daerah, dan kearifan lokal nusantara',
            'Teknologi dan Desain' => 'Buku arsitektur, desain grafis, interior, dan rekayasa teknologi',
            'Sains dan Kesehatan' => 'Ilmu pengetahuan alam, kesehatan medis, dan herbal tradisional',
            'Biografi' => 'Kisah inspiratif tokoh bangsa, ulama besar, dan ilmuwan terkemuka dunia',
        ];

        foreach ($kategoriList as $nama => $deskripsi) {
            Kategori::firstOrCreate(
                ['nama_kategori' => $nama],
                ['deskripsi' => $deskripsi]
            );
        }

        Kategori::where('nama_kategori', 'Lainnya')->delete();

        $this->call(KoleksiPerpustakaanSeeder::class);
    }
}
