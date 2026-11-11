<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KoleksiPerpustakaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriList = [
            'Pelajaran' => 'Buku paket sekolah, kamus, dan modul belajar',
            'Novel' => 'Karya sastra dan novel fiksi best seller',
            'Pengembangan Diri' => 'Buku motivasi, pengembangan diri, dan psikologi terapan',
            'Bisnis dan Ekonomi' => 'Buku finansial, investasi, dan bisnis praktis',
            'Komik' => 'Koleksi komik bergambar populer dan manga',
            'Agama' => 'Kajian sejarah, fiqih, dan literatur keagamaan',
            'Hukum dan Politik' => 'Kajian tata negara, hukum perundang-undangan',
            'Resep' => 'Aneka resep masakan nusantara dan internasional',
            'Lainnya' => 'Kategori bacaan umum dan ensiklopedia',
        ];

        $kategoriMap = [];
        foreach ($kategoriList as $nama => $deskripsi) {
            $kat = Kategori::firstOrCreate(
                ['nama_kategori' => $nama],
                ['deskripsi' => $deskripsi]
            );
            $kategoriMap[$nama] = $kat->id;
        }

        $jsonPath = database_path('seeders/data/koleksi_500_buku.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $books = json_decode(File::get($jsonPath), true);
        if (! is_array($books) || empty($books)) {
            return;
        }

        $existingIsbns = DB::table('bukus')->pluck('id', 'isbn')->all();
        $fallbackKategoriId = $kategoriMap['Lainnya'] ?? array_values($kategoriMap)[0];

        $toInsert = [];
        $now = now();

        foreach ($books as $item) {
            $isbn = (string) ($item['isbn'] ?? '');
            if (! $isbn) {
                continue;
            }

            $catId = $kategoriMap[$item['kategori'] ?? ''] ?? $fallbackKategoriId;

            $record = [
                'kategori_id' => $catId,
                'judul' => (string) ($item['judul'] ?? 'Buku Perpustakaan'),
                'pengarang' => (string) ($item['pengarang'] ?? 'Tim Pustaka'),
                'penerbit' => (string) ($item['penerbit'] ?? 'SMKN 5 Solo'),
                'tanggal_terbit' => ! empty($item['tanggal_terbit']) ? $item['tanggal_terbit'] : null,
                'tahun_terbit' => (int) ($item['tahun_terbit'] ?? 2024),
                'isbn' => $isbn,
                'stok' => (int) ($item['stok'] ?? 10),
                'cover_image' => ! empty($item['cover_image']) ? $item['cover_image'] : null,
                'sinopsis' => ! empty($item['sinopsis']) ? $item['sinopsis'] : null,
                'tebal_halaman' => (int) ($item['tebal_halaman'] ?? 250),
                'bahasa' => (string) ($item['bahasa'] ?? 'Indonesia'),
                'views_count' => (int) ($item['views_count'] ?? 0),
                'reads_yesterday' => (int) ($item['reads_yesterday'] ?? 0),
                'updated_at' => $now,
            ];

            if (isset($existingIsbns[$isbn])) {
                // Update existing record if needed
                DB::table('bukus')->where('id', $existingIsbns[$isbn])->update($record);
            } else {
                $record['created_at'] = $now;
                $toInsert[] = $record;
            }
        }

        if (! empty($toInsert)) {
            foreach (array_chunk($toInsert, 100) as $chunk) {
                DB::table('bukus')->insertOrIgnore($chunk);
            }
        }
    }
}
