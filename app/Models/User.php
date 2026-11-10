<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'role',
    'is_active',
    'google_id',
    'avatar',
    'profile_color',
    'no_telepon',
    'no_whatsapp',
    'tanggal_lahir',
    'nisn',
    'nis',
    'nama_sekolah',
    'tahun_ajaran',
    'nama_kelas',
    'alamat',
    'email_verified_at',
    'verification_code',
    'verification_code_expires_at',
])]
#[Hidden(['password', 'remember_token', 'verification_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, array{label: string, bg: string, text: string, ring: string, banner: string}>
     */
    public const PROFILE_COLORS = [
        'blue' => [
            'label' => 'Biru Langit',
            'bg' => '#dbeafe',
            'text' => '#1d4ed8',
            'ring' => '#60a5fa',
            'banner' => 'linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #4f46e5 100%)',
        ],
        'indigo' => [
            'label' => 'Ungu Lembut',
            'bg' => '#e0e7ff',
            'text' => '#4338ca',
            'ring' => '#818cf8',
            'banner' => 'linear-gradient(135deg, #3730a3 0%, #4f46e5 55%, #7c3aed 100%)',
        ],
        'rose' => [
            'label' => 'Merah Koral',
            'bg' => '#ffe4e6',
            'text' => '#be123c',
            'ring' => '#fb7185',
            'banner' => 'linear-gradient(135deg, #9f1239 0%, #e11d48 55%, #f43f5e 100%)',
        ],
        'emerald' => [
            'label' => 'Hijau Mint',
            'bg' => '#d1fae5',
            'text' => '#047857',
            'ring' => '#34d399',
            'banner' => 'linear-gradient(135deg, #065f46 0%, #059669 55%, #10b981 100%)',
        ],
        'amber' => [
            'label' => 'Kuning Hangat',
            'bg' => '#fef3c7',
            'text' => '#b45309',
            'ring' => '#fbbf24',
            'banner' => 'linear-gradient(135deg, #92400e 0%, #d97706 55%, #f59e0b 100%)',
        ],
        'pink' => [
            'label' => 'Merah Muda',
            'bg' => '#fce7f3',
            'text' => '#be185d',
            'ring' => '#f472b6',
            'banner' => 'linear-gradient(135deg, #9d174d 0%, #db2777 55%, #ec4899 100%)',
        ],
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPustakawan(): bool
    {
        return $this->role === 'pustakawan';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'pustakawan'], true);
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null || $this->isStaff();
    }

    public function isSchoolIdentityComplete(): bool
    {
        if ($this->isStaff()) {
            return true;
        }

        $nisnValid = preg_match('/^\d{10}$/', (string) $this->nisn) === 1;
        $nisTrimmed = trim((string) $this->nis);
        $nisValid = $nisTrimmed !== '' && mb_strlen($nisTrimmed) <= 5;

        return $nisnValid
            && $nisValid
            && ! empty(trim((string) $this->no_telepon))
            && ! empty(trim((string) $this->no_whatsapp))
            && $this->tanggal_lahir !== null
            && ! empty(trim((string) $this->nama_sekolah))
            && ! empty(trim((string) $this->tahun_ajaran))
            && ! empty(trim((string) $this->nama_kelas))
            && ! empty(trim((string) $this->alamat));
    }

    public function getIdentityCompletionPercentageAttribute(): int
    {
        $nisTrimmed = trim((string) $this->nis);

        $fields = [
            ! empty(trim((string) $this->no_telepon)),
            ! empty(trim((string) $this->no_whatsapp)),
            $this->tanggal_lahir !== null,
            preg_match('/^\d{10}$/', (string) $this->nisn) === 1,
            $nisTrimmed !== '' && mb_strlen($nisTrimmed) <= 5,
            ! empty(trim((string) $this->nama_sekolah)),
            ! empty(trim((string) $this->tahun_ajaran)),
            ! empty(trim((string) $this->nama_kelas)),
            ! empty(trim((string) $this->alamat)),
        ];

        $filled = count(array_filter($fields));

        return (int) round(($filled / count($fields)) * 100);
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

    public function getDisplayUsernameAttribute(): string
    {
        if (! empty($this->username)) {
            return $this->username;
        }

        $base = Str::slug($this->name, '');

        return ($base !== '' ? $base : 'pembaca').$this->id;
    }

    public function getInitialAttribute(): string
    {
        $trimmed = trim((string) $this->name);

        if ($trimmed === '') {
            return 'P';
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 1));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar) || Str::contains($this->avatar, 'ui-avatars.com')) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        return asset(ltrim($this->avatar, '/'));
    }

    /**
     * @return array{label: string, bg: string, text: string, ring: string}
     */
    public function getColorPaletteAttribute(): array
    {
        $key = $this->profile_color ?? 'blue';

        return self::PROFILE_COLORS[$key] ?? self::PROFILE_COLORS['blue'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
