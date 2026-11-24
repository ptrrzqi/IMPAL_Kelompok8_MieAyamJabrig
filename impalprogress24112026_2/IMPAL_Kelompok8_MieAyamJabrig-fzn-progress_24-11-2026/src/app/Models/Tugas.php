<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entity: Materi/Tugas/Quiz Item (DPPL #5).
 */
class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = [
        'matakuliah_id', 'minggu_ke', 'judul_tugas', 'deskripsi', 'waktu_buka', 'deadline',
        'file_lampiran', 'nama_lampiran',
    ];

    protected function casts(): array
    {
        return [
            'waktu_buka' => 'datetime',
            'deadline'   => 'datetime',
        ];
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'matakuliah_id');
    }

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'tugas_id');
    }

    public function isBelumDibuka(): bool
    {
        return now()->lt($this->waktu_buka);
    }

    public function isLewatDeadline(): bool
    {
        return now()->gt($this->deadline);
    }

    /** Label status: Belum Dibuka / Dibuka / Ditutup. */
    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->isBelumDibuka()   => 'Belum Dibuka',
            $this->isLewatDeadline() => 'Ditutup',
            default                  => 'Dibuka',
        };
    }
}
