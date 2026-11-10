<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class LaskarPelangiSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriNovel = Kategori::firstOrCreate(['nama_kategori' => 'Novel']);

        Buku::updateOrCreate(
            ['isbn' => '979-3062-79-7'],
            [
                'kategori_id' => $kategoriNovel->id,
                'judul' => 'Laskar Pelangi (Edisi Spesial 15 Tahun)',
                'pengarang' => 'Andrea Hirata',
                'penerbit' => 'Bentang Pustaka',
                'tahun_terbit' => 2005,
                'tebal_halaman' => 529,
                'bahasa' => 'Indonesia',
                'stok' => 5,
                'cover_image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',
                'sinopsis' => 'Laskar Pelangi adalah novel pertama karya Andrea Hirata yang diterbitkan oleh Bentang Pustaka pada tahun 2005. Novel ini bercerita tentang kehidupan 10 anak dari keluarga miskin yang bersekolah (SD dan SMP) di sebuah sekolah Muhammadiyah di pulau Belitong yang penuh dengan keterbatasan. Dengan semangat juang yang tinggi, bimbingan penuh kasih dari Bu Muslimah dan Pak Harfan, anak-anak tersebut yang menamai diri mereka sebagai Laskar Pelangi berhasil menembus berbagai rintangan kehidupan demi meraih mimpi mereka.',
                'views_count' => 0,
                'reads_yesterday' => 0,
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?auto=format&fit=crop&w=800&q=80',
                ],
            ]
        );

        Buku::whereNull('tebal_halaman')->orWhere('tebal_halaman', 0)->update(['tebal_halaman' => 320]);
        Buku::whereNull('bahasa')->update(['bahasa' => 'Indonesia']);
    }
}
