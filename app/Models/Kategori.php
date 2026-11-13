<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $guarded = ['id'];

    public function bukus(): HasMany
    {
        return $this->hasMany(Buku::class);
    }

    /**
     * Get the category display name translated for the active application locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $raw = trim((string) $this->nama_kategori);
        if ($raw === '') {
            return __('Umum');
        }

        $translated = __($raw);
        if ($translated !== $raw) {
            return $translated;
        }

        if (app()->getLocale() === 'en') {
            $map = [
                'agama' => 'Religion',
                'biografi' => 'Biography',
                'bisnis' => 'Business',
                'bisnis dan ekonomi' => 'Business & Economics',
                'ensiklopedia' => 'Encyclopedia',
                'fiksi' => 'Fiction',
                'filsafat' => 'Philosophy',
                'hukum dan politik' => 'Law & Politics',
                'kamus' => 'Dictionary',
                'kesehatan' => 'Health',
                'komik' => 'Comics',
                'novel' => 'Novels',
                'olahraga' => 'Sports',
                'pelajaran' => 'Textbooks',
                'pengembangan diri' => 'Self-Development',
                'psikologi' => 'Psychology',
                'resep' => 'Recipes',
                'sains' => 'Science',
                'sains dan kesehatan' => 'Science & Health',
                'sastra' => 'Literature',
                'sejarah' => 'History',
                'sejarah dan budaya' => 'History & Culture',
                'seni' => 'Arts',
                'seni dan desain' => 'Arts & Design',
                'teknologi' => 'Technology',
                'teknologi dan desain' => 'Technology & Design',
                'teknologi informasi' => 'Information Technology',
                'umum' => 'General',
            ];

            $lower = mb_strtolower($raw);
            if (isset($map[$lower])) {
                return $map[$lower];
            }
        }

        return $raw;
    }
}
