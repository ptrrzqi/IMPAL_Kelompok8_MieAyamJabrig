<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    /**
     * P003 Detail Mata Kuliah: materi & tugas dikelompokkan per minggu.
     * Alternative flow: pengguna tidak terdaftar -> P002 "Tidak Terdaftar".
     */
    public function show(MataKuliah $mataKuliah): View
    {
        $user = Auth::user();

        if (! $mataKuliah->canBeAccessedBy($user)) {
            return view('matakuliah.tidak-terdaftar', ['mataKuliah' => $mataKuliah]);
        }

        $mataKuliah->load(['dosen', 'materi' => fn ($q) => $q->orderBy('minggu_ke')->orderBy('id')]);
        $tugas = $mataKuliah->tugas()
            ->withCount('pengumpulan')
            ->with(['pengumpulan' => fn ($q) => $q->where('mahasiswa_id', $user->id)])
            ->orderBy('minggu_ke')->orderBy('id')->get();

        $minggu = $mataKuliah->materi->pluck('minggu_ke')->merge($tugas->pluck('minggu_ke'))->unique()->sort()->values();
        $canManage = $mataKuliah->canBeManagedBy($user);
        $mahasiswa = $canManage ? $mataKuliah->mahasiswa()->orderBy('name')->get() : collect();

        $progress = $user->isMahasiswa()
            ? (int) optional($mataKuliah->mahasiswa()->whereKey($user->id)->first())->pivot?->progress
            : null;

        return view('matakuliah.show', compact('mataKuliah', 'tugas', 'minggu', 'canManage', 'mahasiswa', 'progress'));
    }
}
