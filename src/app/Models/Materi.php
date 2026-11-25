<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Weekly Module / Materi Mingguan (DPPL #5).
 */
class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'matakuliah_id', 'minggu_ke', 'judul_materi', 'deskripsi', 'file_path', 'file_name',
    ];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'matakuliah_id');
    }
}
