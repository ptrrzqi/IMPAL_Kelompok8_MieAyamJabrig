<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * P001 Home + P002 Main Course (FR-04): daftar mata kuliah pengguna,
     * dengan pencarian, filter status, dan pengurutan.
     */
    public function index(Request $request): View
    {
        $user   = Auth::user();
        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'semua');
        $sort   = $request->query('sort', 'nama_asc');

        $query = MataKuliah::query()->with('dosen');

        if ($user->isMahasiswa()) {
            $query->join('main_course', 'main_course.matakuliah_id', '=', 'mata_kuliah.id')
                ->where('main_course.user_id', $user->id)
                ->select('mata_kuliah.*', 'main_course.status_course', 'main_course.progress');
            if (in_array($status, ['aktif', 'selesai'], true)) {
                $query->where('main_course.status_course', $status);
            }
        } else {
            if ($user->isDosen()) {
                $query->where('dosen_id', $user->id);
            }
            if (in_array($status, ['aktif', 'selesai'], true)) {
                $query->where('status_aktif', $status === 'aktif');
            }
        }

        if ($q !== '') {
            $query->where(fn ($w) => $w->where('nama_mk', 'like', "%{$q}%")->orWhere('kode_mk', 'like', "%{$q}%"));
        }

        match ($sort) {
            'nama_desc' => $query->orderByDesc('nama_mk'),
            'kode'      => $query->orderBy('kode_mk'),
            default     => $query->orderBy('nama_mk'),
        };

        $courses = $query->withCount(['materi', 'tugas', 'mahasiswa'])->get();

        // Total enrollment pengguna (tanpa filter) untuk membedakan "tidak terdaftar" vs "tidak ditemukan"
        $totalTerdaftar = match (true) {
            $user->isMahasiswa() => $user->mataKuliahDiikuti()->count(),
            $user->isDosen()     => $user->mataKuliahDiampu()->count(),
            default              => MataKuliah::count(),
        };

        return view('dashboard.index', compact('user', 'courses', 'q', 'status', 'sort', 'totalTerdaftar'));
    }
}
