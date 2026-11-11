<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Buku extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gallery_images' => 'array',
            'tanggal_terbit' => 'date',
            'tahun_terbit' => 'integer',
            'tebal_halaman' => 'integer',
            'stok' => 'integer',
            'views_count' => 'integer',
            'reads_yesterday' => 'integer',
        ];
    }

    public function getTanggalTerbitFormattedAttribute(): string
    {
        if ($this->tanggal_terbit instanceof CarbonInterface) {
            $bulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            return $this->tanggal_terbit->format('d').' '.($bulan[(int) $this->tanggal_terbit->format('n')] ?? $this->tanggal_terbit->format('F')).' '.$this->tanggal_terbit->format('Y');
        }

        return $this->tahun_terbit ? (string) $this->tahun_terbit : '-';
    }

    public function getTanggalTerbitInputAttribute(): string
    {
        if ($this->tanggal_terbit instanceof CarbonInterface) {
            return $this->tanggal_terbit->format('Y-m-d');
        }

        if ($this->tahun_terbit) {
            return sprintf('%04d-01-15', (int) $this->tahun_terbit);
        }

        return now()->format('Y-m-d');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function getCoverUrlAttribute(): string
    {
        if (empty($this->cover_image)) {
            return 'https://placehold.co/400x600/1e293b/ffffff?text='.urlencode($this->judul);
        }

        if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
            return $this->cover_image;
        }

        return asset(ltrim($this->cover_image, '/'));
    }

    public function getAverageRatingAttribute(): float
    {
        if (array_key_exists('ratings_avg_bintang', $this->attributes)) {
            $avg = $this->attributes['ratings_avg_bintang'];

            return $avg !== null ? round((float) $avg, 1) : 0.0;
        }

        if ($this->relationLoaded('ratings')) {
            $avg = $this->ratings->avg('bintang');

            return $avg !== null ? round((float) $avg, 1) : 0.0;
        }

        $avg = $this->ratings()->avg('bintang');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    public function getTotalRatingsCountAttribute(): int
    {
        if (array_key_exists('ratings_count', $this->attributes)) {
            return (int) $this->attributes['ratings_count'];
        }

        if ($this->relationLoaded('ratings')) {
            return $this->ratings->count();
        }

        return $this->ratings()->count();
    }

    public function getSideImageRawAttribute(): ?string
    {
        $gallery = is_array($this->gallery_images) ? $this->gallery_images : [];

        return ! empty($gallery[0]) ? $gallery[0] : null;
    }

    public function getBackImageRawAttribute(): ?string
    {
        $gallery = is_array($this->gallery_images) ? $this->gallery_images : [];

        return ! empty($gallery[1]) ? $gallery[1] : null;
    }

    public function getSideImageUrlAttribute(): ?string
    {
        $raw = $this->side_image_raw;
        if (empty($raw)) {
            return null;
        }

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        return asset(ltrim($raw, '/'));
    }

    public function getBackImageUrlAttribute(): ?string
    {
        $raw = $this->back_image_raw;
        if (empty($raw)) {
            return null;
        }

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        return asset(ltrim($raw, '/'));
    }

    /**
     * @return array<int, array{label: string, title: string, url: string}>
     */
    public function getPreviewImagesAttribute(): array
    {
        $previews = [
            [
                'label' => '1',
                'title' => __('Sampul Depan'),
                'url' => $this->cover_url,
            ],
        ];

        if ($this->side_image_url) {
            $previews[] = [
                'label' => (string) (count($previews) + 1),
                'title' => __('Tampak Samping'),
                'url' => $this->side_image_url,
            ];
        }

        if ($this->back_image_url) {
            $previews[] = [
                'label' => (string) (count($previews) + 1),
                'title' => __('Sampul Belakang'),
                'url' => $this->back_image_url,
            ];
        }

        return $previews;
    }
}
