<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjamans';

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'integer',
        'tanggal_pinjam' => 'date',
        'tenggat_kembali' => 'date',
        'tanggal_kembali' => 'date',
        'approved_at' => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getProgressStepAttribute(): int
    {
        return match ($this->status) {
            'menunggu_approval' => 2,
            'sedang_dipinjam', 'terlambat' => 3,
            'selesai' => 4,
            'ditolak' => 0,
            default => 2,
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        return match ($this->status) {
            'menunggu_approval' => 42,
            'sedang_dipinjam', 'terlambat' => 75,
            'selesai' => 100,
            'ditolak' => 100,
            default => 42,
        };
    }
}
