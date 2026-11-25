<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Project/Task Submission (DPPL #5).
 */
class PengumpulanTugas extends Model
{
    protected $table = 'pengumpulan_tugas';

    protected $fillable = [
        'tugas_id', 'mahasiswa_id', 'file_tugas', 'nama_file', 'catatan', 'waktu_kumpul', 'nilai', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kumpul' => 'datetime',
            'nilai'        => 'decimal:2',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function isTerlambat(): bool
    {
        return $this->waktu_kumpul->gt($this->tugas->deadline);
    }
}
