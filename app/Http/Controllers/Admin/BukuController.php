<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Models\Kategori;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class BukuController extends Controller
{
    public function index(Request $request): View
    {
        $query = Buku::with('kategori');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%'.$search.'%')
                    ->orWhere('pengarang', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
            });
        }

        $bukus = $query->latest()->paginate(10)->withQueryString();
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.buku.index', compact('bukus', 'kategoris'));
    }

    public function create(): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.buku.create', compact('kategoris'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $resolvedDates = $this->resolveTanggalDanTahunTerbit($request);
        $request->merge($resolvedDates);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'pengarang' => 'required|string|max:255',
            'penerbit' => 'required|string|max:255',
            'tanggal_terbit' => 'required|date',
            'tahun_terbit' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'isbn' => 'required|string|max:50|unique:bukus,isbn',
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'tebal_halaman' => 'nullable|integer|min:1',
            'bahasa' => 'nullable|string|max:100',
            'cover_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'cover_image' => 'nullable|string|max:500',
            'side_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'side_image' => 'nullable|string|max:500',
            'back_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'back_image' => 'nullable|string|max:500',
            'sinopsis' => 'nullable|string',
        ]);

        // 1. Gambar Depan (Sampul Utama)
        if ($request->hasFile('cover_file')) {
            $validated['cover_image'] = $this->storeCoverFile($request->file('cover_file'), 'cover_front');
        } elseif (! empty($validated['cover_image'])) {
            $validated['cover_image'] = trim($validated['cover_image']);
        } else {
            $validated['cover_image'] = 'https://placehold.co/300x450/1e293b/ffffff?text='.urlencode($validated['judul']);
        }

        // 2 & 3. Gambar Samping dan Belakang (jika opsi diaktifkan)
        if ($request->boolean('use_extra_images')) {
            $sideImage = null;
            if ($request->hasFile('side_file')) {
                $sideImage = $this->storeCoverFile($request->file('side_file'), 'cover_side');
            } elseif (! empty($validated['side_image'])) {
                $sideImage = trim($validated['side_image']);
            }

            $backImage = null;
            if ($request->hasFile('back_file')) {
                $backImage = $this->storeCoverFile($request->file('back_file'), 'cover_back');
            } elseif (! empty($validated['back_image'])) {
                $backImage = trim($validated['back_image']);
            }

            $validated['gallery_images'] = ($sideImage || $backImage) ? [$sideImage, $backImage] : null;
        } else {
            $validated['gallery_images'] = null;
        }

        unset(
            $validated['cover_file'],
            $validated['side_file'],
            $validated['side_image'],
            $validated['back_file'],
            $validated['back_image']
        );

        $validated['views_count'] = 0;
        $validated['reads_yesterday'] = 0;

        $buku = Buku::create($validated);
        $buku->load('kategori');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Buku baru berhasil ditambahkan ke katalog perpustakaan!',
                'buku' => [
                    'id' => $buku->id,
                    'judul' => $buku->judul,
                    'pengarang' => $buku->pengarang,
                    'penerbit' => $buku->penerbit,
                    'tanggal_terbit' => $buku->tanggal_terbit?->format('Y-m-d'),
                    'tanggal_terbit_formatted' => $buku->tanggal_terbit_formatted,
                    'tahun_terbit' => $buku->tahun_terbit,
                    'isbn' => $buku->isbn,
                    'stok' => (int) $buku->stok,
                    'kategori_id' => $buku->kategori_id,
                    'kategori_nama' => $buku->kategori?->nama_kategori ?? 'Umum',
                    'cover_url' => $buku->cover_url,
                    'edit_url' => route('pustakawan.buku.edit', $buku->id),
                ],
            ]);
        }

        return redirect()->route('pustakawan.buku.index')->with('success', 'Buku baru berhasil ditambahkan ke katalog!');
    }

    public function edit(Buku $buku): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.buku.edit', compact('buku', 'kategoris'));
    }

    public function update(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        $resolvedDates = $this->resolveTanggalDanTahunTerbit($request, $buku);
        $request->merge($resolvedDates);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'pengarang' => 'required|string|max:255',
            'penerbit' => 'required|string|max:255',
            'tanggal_terbit' => 'required|date',
            'tahun_terbit' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'isbn' => 'required|string|max:50|unique:bukus,isbn,'.$buku->id,
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'tebal_halaman' => 'nullable|integer|min:1',
            'bahasa' => 'nullable|string|max:100',
            'cover_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'cover_image' => 'nullable|string|max:500',
            'side_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'side_image' => 'nullable|string|max:500',
            'back_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'back_image' => 'nullable|string|max:500',
            'sinopsis' => 'nullable|string',
        ]);

        // 1. Gambar Depan
        if ($request->hasFile('cover_file')) {
            $validated['cover_image'] = $this->storeCoverFile($request->file('cover_file'), 'cover_front');
        } elseif (! empty($validated['cover_image'])) {
            $validated['cover_image'] = trim($validated['cover_image']);
        } else {
            unset($validated['cover_image']);
        }

        // 2 & 3. Gambar Samping dan Belakang (jika opsi diaktifkan)
        if ($request->boolean('use_extra_images')) {
            $existingGallery = is_array($buku->gallery_images) ? $buku->gallery_images : [];
            $sideImage = $existingGallery[0] ?? null;
            $backImage = $existingGallery[1] ?? null;

            if ($request->hasFile('side_file')) {
                $sideImage = $this->storeCoverFile($request->file('side_file'), 'cover_side');
            } elseif (! empty($validated['side_image'])) {
                $sideImage = trim($validated['side_image']);
            }

            if ($request->hasFile('back_file')) {
                $backImage = $this->storeCoverFile($request->file('back_file'), 'cover_back');
            } elseif (! empty($validated['back_image'])) {
                $backImage = trim($validated['back_image']);
            }

            $validated['gallery_images'] = ($sideImage || $backImage) ? [$sideImage, $backImage] : null;
        } else {
            $validated['gallery_images'] = null;
        }

        unset(
            $validated['cover_file'],
            $validated['side_file'],
            $validated['side_image'],
            $validated['back_file'],
            $validated['back_image']
        );

        $buku->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data buku berhasil diperbarui!',
            ]);
        }

        return redirect()->route('pustakawan.buku.index')->with('success', 'Data buku berhasil diperbarui!');
    }

    public function destroy(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        $judul = $buku->judul;
        $buku->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Buku \"{$judul}\" berhasil dihapus dari katalog!",
            ]);
        }

        return redirect()->route('pustakawan.buku.index')->with('success', "Buku \"{$judul}\" berhasil dihapus dari katalog!");
    }

    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:bukus,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 buku yang ingin dihapus.',
            'ids.min' => 'Pilih minimal 1 buku yang ingin dihapus.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $deletedCount = Buku::whereIn('id', $ids)->delete();

        $msg = "{$deletedCount} buku yang dipilih berhasil dihapus dari katalog!";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => $msg,
            ]);
        }

        return redirect()->route('pustakawan.buku.index')->with('success', $msg);
    }

    public function updateStock(Request $request, Buku $buku): JsonResponse
    {
        $validated = $request->validate([
            'stok' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $buku->update([
            'stok' => (int) $validated['stok'],
        ]);

        return response()->json([
            'success' => true,
            'stok' => (int) $buku->stok,
            'message' => "Stok buku \"{$buku->judul}\" diperbarui menjadi {$buku->stok} eksemplar.",
        ]);
    }

    public function quickStoreKategori(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:kategoris,nama_kategori'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Kategori ini sudah tersedia.',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => trim($validated['nama_kategori']),
            'deskripsi' => $validated['deskripsi'] ?? 'Koleksi buku '.trim($validated['nama_kategori']),
        ]);

        return response()->json([
            'success' => true,
            'kategori' => [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'deskripsi' => $kategori->deskripsi,
            ],
            'message' => "Kategori \"{$kategori->nama_kategori}\" berhasil ditambahkan!",
        ]);
    }

    /**
     * @return array{tanggal_terbit: string, tahun_terbit: int}
     */
    private function resolveTanggalDanTahunTerbit(Request $request, ?Buku $buku = null): array
    {
        if ($request->filled('tanggal_terbit')) {
            try {
                $dt = Carbon::parse((string) $request->input('tanggal_terbit'));

                return [
                    'tanggal_terbit' => $dt->format('Y-m-d'),
                    'tahun_terbit' => (int) $dt->format('Y'),
                ];
            } catch (\Throwable) {
            }
        }

        if ($request->filled('tgl_tahun')) {
            $year = max(1900, min((int) date('Y') + 1, (int) $request->input('tgl_tahun')));
            $month = max(1, min(12, (int) $request->input('tgl_bulan', 1)));
            $maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $day = max(1, min($maxDay, (int) $request->input('tgl_hari', 1)));

            return [
                'tanggal_terbit' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                'tahun_terbit' => $year,
            ];
        }

        if ($request->filled('tahun_terbit')) {
            $year = max(1900, min((int) date('Y') + 1, (int) $request->input('tahun_terbit')));

            return [
                'tanggal_terbit' => sprintf('%04d-01-15', $year),
                'tahun_terbit' => $year,
            ];
        }

        if ($buku && $buku->tanggal_terbit) {
            return [
                'tanggal_terbit' => $buku->tanggal_terbit->format('Y-m-d'),
                'tahun_terbit' => (int) $buku->tanggal_terbit->format('Y'),
            ];
        }

        $now = now();

        return [
            'tanggal_terbit' => $now->format('Y-m-d'),
            'tahun_terbit' => (int) $now->format('Y'),
        ];
    }

    private function storeCoverFile(UploadedFile $file, string $prefix): string
    {
        $filename = $prefix.'_'.time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $targetDir = is_dir('/data') ? '/data/covers' : public_path('covers');

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        return is_dir('/data') ? '/media/covers/'.$filename : '/covers/'.$filename;
    }
}
