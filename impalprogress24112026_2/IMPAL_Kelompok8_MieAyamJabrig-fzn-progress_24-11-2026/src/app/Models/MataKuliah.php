<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entity: Course / Database Course (DPPL #4, #5).
 */
class MataKuliah extends Model
{
    protected $table = 'mata_kuliah';

    protected $fillable = [
        'kode_mk', 'nama_mk', 'dosen_id', 'semester', 'deskripsi', 'status_aktif', 'warna',
    ];

    protected function casts(): array
    {
        return ['status_aktif' => 'boolean'];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function mahasiswa(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'main_course', 'matakuliah_id', 'user_id')
            ->withPivot(['status_course', 'progress'])
            ->withTimestamps();
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'matakuliah_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'matakuliah_id');
    }

    /** Apakah user boleh membuka mata kuliah ini (EnrollmentStatus controller). */
    public function canBeAccessedBy(User $user): bool
    {
        if ($user->isAdmin() || (int) $this->dosen_id === (int) $user->id) {
            return true;
        }

        return $this->mahasiswa()->whereKey($user->id)->exists();
    }

    /** Apakah user boleh mengelola materi/tugas/nilai (dosen pengampu atau admin). */
    public function canBeManagedBy(User $user): bool
    {
        return $user->isAdmin() || ($user->isDosen() && (int) $this->dosen_id === (int) $user->id);
    }

    /** Hitung ulang progress mahasiswa = tugas terkumpul / total tugas. */
    public function recalculateProgress(User $mahasiswa): int
    {
        $total = $this->tugas()->count();
        $done = $total === 0 ? 0 : PengumpulanTugas::where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('tugas_id', $this->tugas()->pluck('id'))
            ->count();
        $progress = $total === 0 ? 0 : (int) round($done / $total * 100);

        $this->mahasiswa()->updateExistingPivot($mahasiswa->id, ['progress' => $progress]);

        return $progress;
    }
}
