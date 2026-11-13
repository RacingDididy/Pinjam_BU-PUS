<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use Database\Seeders\BukuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_books_and_categories(): void
    {
        $this->seed(BukuSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Atomic Habits');
        $response->assertSee('Pelajaran');
        $response->assertDontSee('<span>Admin</span>', false);
    }

    public function test_search_filter_returns_matching_books(): void
    {
        $this->seed(BukuSeeder::class);

        $response = $this->get('/perpustakaan?search=Laskar');

        $response->assertStatus(200);
        $response->assertSee('Laskar Pelangi');
        $response->assertDontSee('Cooking Basics');
    }

    public function test_category_filter_returns_matching_books(): void
    {
        $this->seed(BukuSeeder::class);

        $komikCategory = Kategori::where('nama_kategori', 'Komik')->first();

        $response = $this->get('/perpustakaan?kategori='.$komikCategory->id);

        $response->assertStatus(200);
        $response->assertSee('Donal Bebek');
    }

    public function test_empty_search_shows_not_found_message(): void
    {
        $this->seed(BukuSeeder::class);

        $response = $this->get('/perpustakaan?search=BukuYangTidakPernahAda');

        $response->assertStatus(200);
        $response->assertSee('Tidak ada buku yang ditemukan');
    }

    public function test_guest_cannot_access_admin_dashboard_and_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/');
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan & Keamanan Sistem');
    }

    public function test_branding_is_pinjam_bu_pus_and_not_sipus(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('PINJAM');
        $response->assertSee('BU-PUS');
        $response->assertDontSee('SIPUS');

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('PINJAM');
        $loginResponse->assertSee('BU-PUS');
        $loginResponse->assertDontSee('SIPUS');

        $registerResponse = $this->get('/register');
        $registerResponse->assertStatus(200);
        $registerResponse->assertSee('PINJAM');
        $registerResponse->assertSee('BU-PUS');
        $registerResponse->assertDontSee('SIPUS');

        $admin = User::factory()->create(['role' => 'admin']);
        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('PINJAM');
        $adminResponse->assertSee('BU-PUS');
        $adminResponse->assertDontSee('SIPUS');
    }

    public function test_admin_dashboard_displays_real_metrics_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = Kategori::create([
            'nama_kategori' => 'Teknologi Informasi',
            'deskripsi' => 'Buku pemrograman dan AI',
        ]);

        Buku::create([
            'judul' => 'Mastering AI 2026',
            'pengarang' => 'John Doe',
            'penerbit' => 'Tech Press',
            'tahun_terbit' => 2026,
            'isbn' => '9781112223334',
            'kategori_id' => $kategori->id,
            'stok' => 12,
            'views_count' => 50,
            'reads_yesterday' => 10,
        ]);

        $response = $this->actingAs($admin)->get('/pustakawan');

        $response->assertStatus(200);
        $response->assertSee('Teknologi Informasi');

        $catalogResponse = $this->actingAs($admin)->get('/pustakawan/buku');
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Mastering AI 2026');
        $catalogResponse->assertSee('12');
    }

    public function test_admin_can_add_book_with_uploaded_image_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = Kategori::create([
            'nama_kategori' => 'Sains',
            'deskripsi' => 'Buku sains',
        ]);

        $file = UploadedFile::fake()->image('cover_test.jpg', 300, 450);

        $response = $this->actingAs($admin)->post('/pustakawan/buku', [
            'judul' => 'Fisika Kuantum Modern',
            'pengarang' => 'Prof. Einstein',
            'penerbit' => 'Sains Indonesia',
            'tahun_terbit' => 2025,
            'isbn' => '9789998887771',
            'kategori_id' => $kategori->id,
            'stok' => 7,
            'cover_file' => $file,
        ]);

        $response->assertRedirect('/pustakawan/buku');

        $buku = Buku::where('isbn', '9789998887771')->first();
        $this->assertNotNull($buku);
        $this->assertStringStartsWith('/covers/cover_', $buku->cover_image);

        // Bersihkan file test yang dibuat di public/covers
        $filePath = public_path(ltrim($buku->cover_image, '/'));
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function test_admin_can_add_book_with_image_url_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = Kategori::create([
            'nama_kategori' => 'Bisnis',
            'deskripsi' => 'Buku bisnis',
        ]);

        $imageUrl = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=400';

        $response = $this->actingAs($admin)->post('/pustakawan/buku', [
            'judul' => 'Startup Playbook 2026',
            'pengarang' => 'Peter Thiel',
            'penerbit' => 'Crown Business',
            'tahun_terbit' => 2026,
            'isbn' => '9785556667772',
            'kategori_id' => $kategori->id,
            'stok' => 15,
            'cover_image' => $imageUrl,
        ]);

        $response->assertRedirect('/pustakawan/buku');

        $buku = Buku::where('isbn', '9785556667772')->first();
        $this->assertNotNull($buku);
        $this->assertEquals($imageUrl, $buku->cover_image);
        $this->assertEquals($imageUrl, $buku->cover_url);
    }

    public function test_registration_issues_email_verification_link_without_numeric_otp(): void
    {
        $response = $this->postJson('/register', [
            'email' => 'siswa.baru@gmail.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'step' => 'waiting_verification',
            'email' => 'siswa.baru@gmail.com',
        ]);
        $response->assertJsonMissing(['verification_code' => true]);

        $user = User::where('email', 'siswa.baru@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->isEmailVerified());
        $this->assertNotEmpty($user->verification_code);

        // Verifikasi melalui tautan email
        $verifyResponse = $this->get(route('verification.link', [
            'id' => $user->id,
            'token' => $user->verification_code,
        ]));

        $verifyResponse->assertRedirect(route('home', ['setup_password' => 1]));
        $this->assertTrue($user->fresh()->isEmailVerified());
        $this->assertAuthenticatedAs($user);
    }

    public function test_category_names_translate_when_locale_is_english(): void
    {
        Kategori::create(['nama_kategori' => 'Teknologi dan Desain', 'deskripsi' => 'Tech & Design']);
        Kategori::create(['nama_kategori' => 'Hukum dan Politik', 'deskripsi' => 'Law & Politics']);
        Kategori::create(['nama_kategori' => 'Pelajaran', 'deskripsi' => 'Textbooks']);

        $response = $this->withSession(['locale' => 'en'])->get('/perpustakaan');

        $response->assertStatus(200);
        $response->assertSee('Technology &amp; Design', false);
        $response->assertSee('Law &amp; Politics', false);
        $response->assertSee('Textbooks');
        $response->assertDontSee('Teknologi dan Desain');
        $response->assertDontSee('Hukum dan Politik');
    }
}
