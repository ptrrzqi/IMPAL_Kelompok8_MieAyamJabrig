<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TugasController extends Controller
{
    /** P004: dosen menambah tugas. */
    public function store(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        abort_unless($mataKuliah->canBeManagedBy(Auth::user()), 403, 'Hanya dosen pengampu yang dapat menambah tugas.');

        $data = $request->validate([
            'minggu_ke'   => ['required', 'integer', 'min:1', 'max:16'],
            'judul_tugas' => ['required', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string', 'max:5000'],
            'waktu_buka'  => ['required', 'date'],
            'deadline'    => ['required', 'date', 'after:waktu_buka'],
            'lampiran'    => ['nullable', 'file', 'max:51200'],
        ], [
            'deadline.after' => 'Deadline harus setelah waktu tugas dibuka.',
            'lampiran.max'   => 'Ukuran lampiran maksimal 50 MB.',
        ]);

        $tugas = $mataKuliah->tugas()->make(collect($data)->except('lampiran')->all());
        if ($request->hasFile('lampiran')) {
            $tugas->file_lampiran = $request->file('lampiran')->store("tugas/{$mataKuliah->id}", 'local');
            $tugas->nama_lampiran = $request->file('lampiran')->getClientOriginalName();
        }
        $tugas->save();

        // progress mahasiswa berubah karena total tugas bertambah
        foreach ($mataKuliah->mahasiswa as $m) {
            $mataKuliah->recalculateProgress($m);
        }

        return back()->with('success', 'Tugas berhasil ditambahkan.');
    }

    /** P006: detail tugas + form submission (mahasiswa) / daftar pengumpulan & penilaian (dosen). */
    public function show(Tugas $tugas): View
    {
        $user = Auth::user();
        $mk   = $tugas->mataKuliah;
        abort_unless($mk->canBeAccessedBy($user), 403, 'Anda tidak terdaftar pada mata kuliah ini.');

        $canManage = $mk->canBeManagedBy($user);
        $submission = $tugas->pengumpulan()->where('mahasiswa_id', $user->id)->first();

        $daftar = collect();
        if ($canManage) {
            $kumpul = $tugas->pengumpulan()->get()->keyBy('mahasiswa_id');
            $daftar = $mk->mahasiswa()->orderBy('name')->get()
                ->map(fn ($m) => ['mahasiswa' => $m, 'pengumpulan' => $kumpul->get($m->id)]);
        }

        return view('tugas.show', compact('tugas', 'mk', 'canManage', 'submission', 'daftar'));
    }

    public function lampiran(Tugas $tugas): StreamedResponse
    {
        abort_unless($tugas->mataKuliah->canBeAccessedBy(Auth::user()), 403);
        abort_unless($tugas->file_lampiran && Storage::disk('local')->exists($tugas->file_lampiran), 404);

        return Storage::disk('local')->download($tugas->file_lampiran, $tugas->nama_lampiran);
    }

    public function destroy(Tugas $tugas): RedirectResponse
    {
        $mk = $tugas->mataKuliah;
        abort_unless($mk->canBeManagedBy(Auth::user()), 403);

        foreach ($tugas->pengumpulan as $p) {
            Storage::disk('local')->delete($p->file_tugas);
        }
        if ($tugas->file_lampiran) {
            Storage::disk('local')->delete($tugas->file_lampiran);
        }
        $tugas->delete();

        foreach ($mk->mahasiswa as $m) {
            $mk->recalculateProgress($m);
        }

        return redirect()->route('matakuliah.show', $mk)->with('success', 'Tugas berhasil dihapus.');
    }
}
