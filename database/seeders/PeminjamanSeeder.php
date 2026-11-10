<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        Peminjaman::query()->delete();

        $bukus = Buku::all();
        if ($bukus->isEmpty()) {
            return;
        }

        $user = User::first();
        $userId = $user ? $user->id : null;

        $borrowers = [
            'Rizky Pratama',
            'Siti Nurhaliza',
            'Budi Santoso',
            'Dewi Anggraini',
            'Ahmad Fauzan',
            'Anisa Rahmawati',
            'Dimas Saputra',
            'Putri Maharani',
            'Hendro Wicaksono',
            'Maya Kartika',
            'Fajar Nugroho',
            'Lestari Utami',
        ];

        // Sedang dipinjam
        for ($i = 0; $i < 6; $i++) {
            $buku = $bukus[$i % $bukus->count()];
            Peminjaman::create([
                'buku_id' => $buku->id,
                'user_id' => $userId,
                'nama_peminjam' => $borrowers[$i % count($borrowers)],
                'tanggal_pinjam' => Carbon::now()->subDays(rand(2, 6)),
                'tenggat_kembali' => Carbon::now()->addDays(rand(3, 8)),
                'tanggal_kembali' => null,
                'status' => 'sedang_dipinjam',
            ]);
        }

        // Terlambat kembali
        for ($i = 0; $i < 2; $i++) {
            $buku = $bukus[($i + 6) % $bukus->count()];
            Peminjaman::create([
                'buku_id' => $buku->id,
                'user_id' => $userId,
                'nama_peminjam' => $borrowers[($i + 6) % count($borrowers)],
                'tanggal_pinjam' => Carbon::now()->subDays(rand(15, 20)),
                'tenggat_kembali' => Carbon::now()->subDays(rand(2, 5)),
                'tanggal_kembali' => null,
                'status' => 'terlambat',
            ]);
        }

        // Menunggu persetujuan
        for ($i = 0; $i < 3; $i++) {
            $buku = $bukus[($i + 8) % $bukus->count()];
            Peminjaman::create([
                'buku_id' => $buku->id,
                'user_id' => $userId,
                'nama_peminjam' => $borrowers[($i + 8) % count($borrowers)],
                'tanggal_pinjam' => Carbon::now()->subHours(rand(1, 12)),
                'tenggat_kembali' => Carbon::now()->addDays(7),
                'tanggal_kembali' => null,
                'status' => 'menunggu_approval',
            ]);
        }

        // Selesai
        for ($i = 0; $i < 10; $i++) {
            $buku = $bukus[($i + 11) % $bukus->count()];
            Peminjaman::create([
                'buku_id' => $buku->id,
                'user_id' => $userId,
                'nama_peminjam' => $borrowers[($i + 2) % count($borrowers)],
                'tanggal_pinjam' => Carbon::now()->subDays(rand(20, 40)),
                'tenggat_kembali' => Carbon::now()->subDays(rand(10, 20)),
                'tanggal_kembali' => Carbon::now()->subDays(rand(11, 21)),
                'status' => 'selesai',
            ]);
        }
    }
}
