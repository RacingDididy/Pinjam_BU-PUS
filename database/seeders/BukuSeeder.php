<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        Buku::query()->delete();
        Kategori::query()->delete();

        $coversDir = public_path('covers');
        if (! File::isDirectory($coversDir)) {
            File::makeDirectory($coversDir, 0755, true);
        }

        // Buat cover SVG
        $makeSvg = function (string $filename, string $bgColor, string $accentColor, string $title, string $author, string $tag = 'BESTSELLER') use ($coversDir): string {
            $path = $coversDir.DIRECTORY_SEPARATOR.$filename;
            $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $authorSafe = htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
            $tagSafe = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 450" width="100%" height="100%">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bgColor}" stop-opacity="1" />
      <stop offset="100%" stop-color="#0f172a" stop-opacity="0.85" />
    </linearGradient>
    <filter id="shadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="4" stdDeviation="6" flood-opacity="0.25"/>
    </filter>
  </defs>
  <rect width="300" height="450" fill="url(#grad)" rx="16"/>
  <rect x="12" y="12" width="276" height="426" fill="none" stroke="{$accentColor}" stroke-width="1.5" stroke-dasharray="4,4" opacity="0.3" rx="12"/>
  
  <!-- Spine shadow -->
  <rect x="0" y="0" width="18" height="450" fill="rgba(0,0,0,0.18)" rx="4"/>
  <line x1="18" y1="0" x2="18" y2="450" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>

  <!-- Badge -->
  <g transform="translate(185, 26)">
    <rect width="90" height="24" rx="12" fill="{$accentColor}" opacity="0.9"/>
    <text x="45" y="16" font-family="'Inter', sans-serif" font-size="9" font-weight="700" fill="#ffffff" text-anchor="middle" letter-spacing="1">{$tagSafe}</text>
  </g>

  <!-- Graphic elements -->
  <circle cx="150" cy="180" r="65" fill="none" stroke="{$accentColor}" stroke-width="2" opacity="0.25"/>
  <circle cx="150" cy="180" r="45" fill="{$accentColor}" opacity="0.15"/>
  <path d="M135 180 L165 180 M150 165 L150 195" stroke="{$accentColor}" stroke-width="3" stroke-linecap="round" opacity="0.6"/>

  <!-- Content -->
  <g transform="translate(30, 290)">
    <text x="0" y="0" font-family="'Inter', sans-serif" font-size="10" font-weight="600" fill="{$accentColor}" letter-spacing="2">EDISI LENGKAP</text>
    <text x="0" y="24" font-family="'Inter', sans-serif" font-size="18" font-weight="800" fill="#ffffff" width="240">
      {$titleSafe}
    </text>
    <text x="0" y="58" font-family="'Inter', sans-serif" font-size="12" font-weight="500" fill="#94a3b8">
      {$authorSafe}
    </text>
  </g>
</svg>
SVG;
            File::put($path, $svg);

            return '/covers/'.$filename;
        };

        $kategoriList = [
            'Pengembangan Diri' => 'Buku motivasi, pengembangan diri, dan psikologi terapan',
            'Petualangan' => 'Kisah petualangan seru dan eksplorasi dunia',
            'Motivasi' => 'Inspirasi dan motivasi meraih kesuksesan hidup',
            'Misteri' => 'Cerita detektif, thriller, dan misteri mendalam',
            'Komik' => 'Koleksi komik bergambar populer dan manga',
            'Aksi' => 'Buku laga dan petualangan penuh tantangan',
            'Komik Anak' => 'Komik edukatif dan menghibur untuk anak-anak',
            'Doraemon' => 'Seri petualangan robot kucing abad 22 Doraemon',
            'Komedi' => 'Karya humor yang menghibur dan mengocok perut',
            'Manga' => 'Komik khas Jepang terjemahan Indonesia',
            'Resep' => 'Aneka resep masakan nusantara dan internasional',
            'Bisnis dan Ekonomi' => 'Buku finansial, investasi, dan bisnis praktis',
            'Agama' => 'Kajian sejarah, fiqih, dan literatur keagamaan',
            'Hukum dan Politik' => 'Kajian tata negara, hukum perundang-undangan',
            'Romansa' => 'Kisah cinta, fiksi remaja, dan romansa modern',
            'Fiksi Petualangan' => 'Novel fiksi aksi dan penjelajahan seru',
            'Pelajaran' => 'Buku paket sekolah, kamus, dan modul belajar',
            'Novel' => 'Karya sastra dan novel fiksi best seller',
            'Lainnya' => 'Kategori bacaan umum dan ensiklopedia',
        ];

        $katModels = [];
        foreach ($kategoriList as $nama => $desc) {
            $katModels[$nama] = Kategori::create([
                'nama_kategori' => $nama,
                'deskripsi' => $desc,
            ]);
        }

        $topBooks = [
            [
                'kategori' => 'Bisnis dan Ekonomi',
                'judul' => 'Sadar Kaya by Mardigu WP',
                'pengarang' => 'Mardigu WP',
                'penerbit' => 'Emang Media',
                'tahun_terbit' => 2019,
                'isbn' => '9786020000011',
                'stok' => 8,
                'cover_image' => $makeSvg('sadar_kaya.svg', '#334155', '#38bdf8', 'Sadar Kaya', 'Mardigu WP', '#1 TOP READ'),
                'sinopsis' => 'Ketika semua usaha sudah dijalankan, semua upaya sudah dikerahkan, tetapi belum juga memberikan hasil yang diinginkan...',
                'views_count' => 18694,
                'reads_yesterday' => 9758,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Atomic Habits',
                'pengarang' => 'James Clear',
                'penerbit' => 'Gramedia Pustaka Utama',
                'tahun_terbit' => 2019,
                'isbn' => '9786020633176',
                'stok' => 15,
                'cover_image' => $makeSvg('atomic_habits.svg', '#854d0e', '#facc15', 'Atomic Habits', 'James Clear', '#2 TOP READ'),
                'sinopsis' => 'An Easy & Proven Way to Build Good Habits & Break Bad Ones. Perubahan kecil yang memberikan hasil luar biasa.',
                'views_count' => 23685,
                'reads_yesterday' => 7832,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Bicara Itu Ada Seninya',
                'pengarang' => 'Oh Su Hyang',
                'penerbit' => 'Bhuana Ilmu Populer',
                'tahun_terbit' => 2018,
                'isbn' => '9786024553920',
                'stok' => 10,
                'cover_image' => $makeSvg('bicara_seninya.svg', '#1e293b', '#38bdf8', 'Bicara Ada Seninya', 'Oh Su Hyang', '#3 TOP READ'),
                'sinopsis' => 'Rahasia komunikasi yang efektif dari pakar komunikasi ternama Korea Selatan untuk memikat lawan bicara.',
                'views_count' => 29544,
                'reads_yesterday' => 6145,
            ],
            [
                'kategori' => 'Pelajaran',
                'judul' => 'Verb 1 2 3 Regular & Irregular',
                'pengarang' => 'Pustaka Siswa',
                'penerbit' => 'Pustaka Pelajar',
                'tahun_terbit' => 2021,
                'isbn' => '9786020000028',
                'stok' => 20,
                'cover_image' => $makeSvg('verb123.svg', '#14532d', '#4ade80', 'Verb 1 2 3 Lengkap', 'Pustaka Siswa', '#4 TOP READ'),
                'sinopsis' => 'Daftar lengkap kata kerja beraturan dan tidak beraturan bahasa Inggris beserta arti dan contoh penggunaannya.',
                'views_count' => 15420,
                'reads_yesterday' => 5909,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Donal Bebek: Paman Gober & Siberat',
                'pengarang' => 'Carl Barks',
                'penerbit' => 'KPG',
                'tahun_terbit' => 2016,
                'isbn' => '9786024241001',
                'stok' => 12,
                'cover_image' => $makeSvg('donal_bebek.svg', '#991b1b', '#f87171', 'Donal Bebek Gober', 'Carl Barks', '#5 TOP READ'),
                'sinopsis' => 'Kisah klasik Paman Gober yang mempertahankan gudang uangnya dari incaran licik Gerombolan Siberat.',
                'views_count' => 29221,
                'reads_yesterday' => 4290,
            ],
        ];

        $latestBooks = [
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Teruslah Bodoh Jangan Pintar',
                'pengarang' => 'Tere Liye',
                'penerbit' => 'Sabak Grip',
                'tahun_terbit' => 2024,
                'isbn' => '9786238829675',
                'stok' => 9,
                'cover_image' => $makeSvg('teruslah_bodoh.svg', '#78350f', '#f59e0b', 'Teruslah Bodoh', 'Tere Liye', 'TERBARU'),
                'sinopsis' => 'Kritik sosial tajam tentang pendidikan, kekuasaan, dan bagaimana masyarakat dibodohi oleh ilusi kepintaran.',
                'views_count' => 1373,
                'reads_yesterday' => 120,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Miiko! Episode 24',
                'pengarang' => 'Eri Ono',
                'penerbit' => 'm&c!',
                'tahun_terbit' => 2022,
                'isbn' => '9786024809012',
                'stok' => 7,
                'cover_image' => $makeSvg('miiko.svg', '#475569', '#cbd5e1', 'Hai, Miiko! 24', 'Eri Ono', 'KOMIK'),
                'sinopsis' => 'Kisah keseharian Miiko Yamada yang ceria dan penuh petualangan seru bersama teman-teman sekolahnya.',
                'views_count' => 192,
                'reads_yesterday' => 45,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Donal Bebek 1425 Edisi Valentine',
                'pengarang' => 'Walt Disney',
                'penerbit' => 'Gramedia',
                'tahun_terbit' => 2017,
                'isbn' => '9786024241025',
                'stok' => 6,
                'cover_image' => $makeSvg('donal_val.svg', '#831843', '#f472b6', 'Donal Valentine', 'Walt Disney', 'SPESIAL'),
                'sinopsis' => 'Edisi spesial hari kasih sayang di Kota Bebek bersama Donal, Desi, dan petualangan kocak lainnya.',
                'views_count' => 1241,
                'reads_yesterday' => 110,
            ],
            [
                'kategori' => 'Resep',
                'judul' => 'Cooking Basics: Masak Praktis',
                'pengarang' => 'Chef Vania',
                'penerbit' => 'Kawan Pustaka',
                'tahun_terbit' => 2020,
                'isbn' => '9789797576012',
                'stok' => 14,
                'cover_image' => $makeSvg('cooking.svg', '#c2410c', '#fb923c', 'Cooking Basics', 'Chef Vania', 'KULINER'),
                'sinopsis' => 'Panduan memasak dasar langkah demi langkah untuk pemula yang ingin mahir membuat masakan lezat di rumah.',
                'views_count' => 212,
                'reads_yesterday' => 60,
            ],
            [
                'kategori' => 'Lainnya',
                'judul' => 'Hands Out Modul Panduan Digital',
                'pengarang' => 'Tim Digital Gapura',
                'penerbit' => 'Kemkominfo',
                'tahun_terbit' => 2021,
                'isbn' => '9786020000035',
                'stok' => 25,
                'cover_image' => $makeSvg('modul_digital.svg', '#0f766e', '#2dd4bf', 'Modul Digital', 'Gapura Digital', 'PANDUAN'),
                'sinopsis' => 'Modul resmi pelatihan transformasi digital untuk pelaku usaha kecil dan menengah di era internet.',
                'views_count' => 1352,
                'reads_yesterday' => 150,
            ],
            [
                'kategori' => 'Hukum dan Politik',
                'judul' => 'Langkah Hati-Hati Omnibus Law',
                'pengarang' => 'Dr. Ahmad Fauzi',
                'penerbit' => 'Sinar Grafika',
                'tahun_terbit' => 2021,
                'isbn' => '9789790078901',
                'stok' => 11,
                'cover_image' => $makeSvg('omnibus_law.svg', '#1e1b4b', '#818cf8', 'Omnibus Law', 'Dr. Ahmad Fauzi', 'HUKUM'),
                'sinopsis' => 'Tinjauan kritis yuridis dan implikasi ketenagakerjaan terhadap undang-undang cipta kerja di Indonesia.',
                'views_count' => 369,
                'reads_yesterday' => 30,
            ],
            [
                'kategori' => 'Agama',
                'judul' => 'Sejarah Islam di Nusantara',
                'pengarang' => 'Prof. Dr. Uka Tjandrasasmita',
                'penerbit' => 'KPG',
                'tahun_terbit' => 2018,
                'isbn' => '9789799102140',
                'stok' => 5,
                'cover_image' => $makeSvg('sejarah_islam.svg', '#854d0e', '#facc15', 'Islam Nusantara', 'Uka Tjandrasasmita', 'SEJARAH'),
                'sinopsis' => 'Rekonstruksi komprehensif jalur penyebaran Islam melalui jalur perdagangan damai dan akulturasi budaya Nusantara.',
                'views_count' => 1411,
                'reads_yesterday' => 88,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => '7 Kesalahan Fatal Pengusaha Pemula',
                'pengarang' => 'Dewa Eka Prayoga',
                'penerbit' => 'Billionaire Store',
                'tahun_terbit' => 2019,
                'isbn' => '9786020000042',
                'stok' => 8,
                'cover_image' => $makeSvg('7_kesalahan.svg', '#581c87', '#c084fc', '7 Kesalahan Fatal', 'Dewa Eka Prayoga', 'BISNIS'),
                'sinopsis' => 'Panduan menghindari jebakan kebangkrutan yang kerap menimpa pebisnis rintisan pemula.',
                'views_count' => 1155,
                'reads_yesterday' => 70,
            ],
            [
                'kategori' => 'Bisnis dan Ekonomi',
                'judul' => 'Ekonomi Mikro Islam: Teori Terapan',
                'pengarang' => 'Adiwarman Karim',
                'penerbit' => 'Rajawali Pers',
                'tahun_terbit' => 2017,
                'isbn' => '9789797690014',
                'stok' => 10,
                'cover_image' => $makeSvg('ekonomi_mikro.svg', '#155e75', '#22d3ee', 'Ekonomi Mikro', 'Adiwarman Karim', 'EKONOMI'),
                'sinopsis' => 'Prinsip-prinsip syariah dalam alokasi sumber daya dan perilaku konsumen ekonomi mikro modern.',
                'views_count' => 575,
                'reads_yesterday' => 40,
            ],
            [
                'kategori' => 'Resep',
                'judul' => 'Soto Lamongan Mantap & Gurih',
                'pengarang' => 'Sisca Soewitomo',
                'penerbit' => 'Gramedia Pustaka Utama',
                'tahun_terbit' => 2019,
                'isbn' => '9786020330012',
                'stok' => 18,
                'cover_image' => $makeSvg('soto.svg', '#a16207', '#fde047', 'Soto Lamongan', 'Sisca Soewitomo', 'KULINER'),
                'sinopsis' => 'Resep otentik kuah kuning gurih berpadu koya udang khas Lamongan yang legendaris.',
                'views_count' => 98,
                'reads_yesterday' => 15,
            ],
            [
                'kategori' => 'Romansa',
                'judul' => 'Perpisahan Sementara',
                'pengarang' => 'Boy Candra',
                'penerbit' => 'Media Kita',
                'tahun_terbit' => 2020,
                'isbn' => '9789797945001',
                'stok' => 12,
                'cover_image' => $makeSvg('perpisahan.svg', '#be185d', '#f472b6', 'Perpisahan', 'Boy Candra', 'ROMANSA'),
                'sinopsis' => 'Kumpulan prosa tentang melepaskan yang harus pergi dan merawat rindu yang tersisa.',
                'views_count' => 137,
                'reads_yesterday' => 22,
            ],
            [
                'kategori' => 'Fiksi Petualangan',
                'judul' => 'Malaikat & Iblis (Angels & Demons)',
                'pengarang' => 'Dan Brown',
                'penerbit' => 'Serambi Ilmu Semesta',
                'tahun_terbit' => 2015,
                'isbn' => '9789790240019',
                'stok' => 6,
                'cover_image' => $makeSvg('dan_brown.svg', '#881337', '#fb7185', 'Malaikat & Iblis', 'Dan Brown', 'THRILLER'),
                'sinopsis' => 'Robert Langdon memecahkan kode konspirasi rahasia persaudaraan Illuminati di Vatikan.',
                'views_count' => 180,
                'reads_yesterday' => 35,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Doraemon Vol 07',
                'pengarang' => 'Fujiko F. Fujio',
                'penerbit' => 'Elex Media Komputindo',
                'tahun_terbit' => 2014,
                'isbn' => '9789792050071',
                'stok' => 16,
                'cover_image' => $makeSvg('doraemon_07.svg', '#0369a1', '#38bdf8', 'Doraemon Vol 07', 'Fujiko F. Fujio', 'FAVORIT'),
                'sinopsis' => 'Kantong ajaib Doraemon kembali menghadirkan alat-alat luar biasa untuk membantu Nobita.',
                'views_count' => 72244,
                'reads_yesterday' => 850,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Berani Tidak Disukai',
                'pengarang' => 'Ichiro Kishimi & Fumitake Koga',
                'penerbit' => 'Gramedia Pustaka Utama',
                'tahun_terbit' => 2019,
                'isbn' => '9786020633213',
                'stok' => 22,
                'cover_image' => $makeSvg('berani.svg', '#1e293b', '#e2e8f0', 'Berani Tidak Disukai', 'Ichiro Kishimi', 'BESTSELLER'),
                'sinopsis' => 'Fenomena psikologi Adlerian tentang bagaimana membebaskan diri, mengubah hidup, dan meraih kebahagiaan sejati.',
                'views_count' => 41112,
                'reads_yesterday' => 620,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Shinchan Vol 01',
                'pengarang' => 'Yoshito Usui',
                'penerbit' => 'Elex Media Komputindo',
                'tahun_terbit' => 2013,
                'isbn' => '9789792010011',
                'stok' => 14,
                'cover_image' => $makeSvg('shinchan.svg', '#b91c1c', '#facc15', 'Shinchan 01', 'Yoshito Usui', 'KOMIK'),
                'sinopsis' => 'Tingkah usil bocah TK Shinnosuke Nohara yang menguji kesabaran Misae dan Hiroshi.',
                'views_count' => 38738,
                'reads_yesterday' => 540,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Kariage Kun 01',
                'pengarang' => 'Masashi Ueda',
                'penerbit' => 'Elex Media Komputindo',
                'tahun_terbit' => 2012,
                'isbn' => '9789792010028',
                'stok' => 10,
                'cover_image' => $makeSvg('kariage.svg', '#15803d', '#86efac', 'Kariage Kun 01', 'Masashi Ueda', 'KOMEDI'),
                'sinopsis' => 'Lelucon khas pegawai kantoran Kariage Shota yang gemar menjahili bos dan rekan kerjanya.',
                'views_count' => 35700,
                'reads_yesterday' => 490,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Seni Berbicara Kepada Siapa Saja',
                'pengarang' => 'Larry King',
                'penerbit' => 'Gramedia Pustaka Utama',
                'tahun_terbit' => 2018,
                'isbn' => '9789792270014',
                'stok' => 19,
                'cover_image' => $makeSvg('seni_berbicara.svg', '#4338ca', '#a5b4fc', 'Seni Berbicara', 'Larry King', 'MOTIVASI'),
                'sinopsis' => 'Kunci sukses komunikasi wawancara dan percakapan sosial oleh pembawa acara talkshow legendaris CNN.',
                'views_count' => 29544,
                'reads_yesterday' => 380,
            ],
            [
                'kategori' => 'Pengembangan Diri',
                'judul' => 'Filosofi Teras',
                'pengarang' => 'Henry Manampiring',
                'penerbit' => 'Penerbit Buku Kompas',
                'tahun_terbit' => 2019,
                'isbn' => '9786024125189',
                'stok' => 30,
                'cover_image' => $makeSvg('filosofi_teras.svg', '#047857', '#34d399', 'Filosofi Teras', 'Henry Manampiring', 'BESTSELLER'),
                'sinopsis' => 'Filsafat Yunani-Romawi Kuno (Stoisisme) untuk mental tangguh di era serba cemas dan overthinking.',
                'views_count' => 26688,
                'reads_yesterday' => 420,
            ],
            [
                'kategori' => 'Komik',
                'judul' => 'Si Juki: Lika-Liku Mahasiswa',
                'pengarang' => 'Faza Meonk',
                'penerbit' => 'Bukune',
                'tahun_terbit' => 2016,
                'isbn' => '9786022201885',
                'stok' => 13,
                'cover_image' => $makeSvg('si_juki.svg', '#c2410c', '#fed7aa', 'Si Juki Komik', 'Faza Meonk', 'KOMIK'),
                'sinopsis' => 'Karakter nyeleneh berparas kocak Si Juki dalam dinamika kuliah, skripsi, dan indekos.',
                'views_count' => 21798,
                'reads_yesterday' => 310,
            ],
            [
                'kategori' => 'Novel',
                'judul' => 'Laskar Pelangi Edisi Spesial 15 Tahun',
                'pengarang' => 'Andrea Hirata',
                'penerbit' => 'Bentang Pustaka',
                'tahun_terbit' => 2020,
                'isbn' => '9786022916964',
                'stok' => 5,
                'cover_image' => $makeSvg('laskar_pelangi.svg', '#1d4ed8', '#93c5fd', 'Laskar Pelangi', 'Andrea Hirata', 'LEGENDARIS'),
                'sinopsis' => 'Edisi istimewa 15 tahun karya fenomena Andrea Hirata mengenai semangat belajar anak-anak Belitong.',
                'views_count' => 19800,
                'reads_yesterday' => 270,
            ],
        ];

        foreach (array_merge($topBooks, $latestBooks) as $data) {
            $katName = $data['kategori'];
            $kategoriId = $katModels[$katName]->id ?? $katModels['Lainnya']->id;

            Buku::create([
                'kategori_id' => $kategoriId,
                'judul' => $data['judul'],
                'pengarang' => $data['pengarang'],
                'penerbit' => $data['penerbit'],
                'tahun_terbit' => $data['tahun_terbit'],
                'isbn' => $data['isbn'],
                'stok' => $data['stok'],
                'cover_image' => $data['cover_image'],
                'sinopsis' => $data['sinopsis'],
                'views_count' => 0,
                'reads_yesterday' => 0,
            ]);
        }
    }
}
