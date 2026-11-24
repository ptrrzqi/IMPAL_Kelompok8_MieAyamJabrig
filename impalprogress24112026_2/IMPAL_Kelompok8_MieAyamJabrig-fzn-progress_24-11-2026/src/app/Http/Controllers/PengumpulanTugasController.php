<?php

namespace App\Http\Controllers;

use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Add Submission (P006) + penilaian tugas oleh dosen.
 */
class PengumpulanTugasController extends Controller
{
    public function store(Request $request, Tugas $tugas): RedirectResponse
    {
        $user = Auth::user();
        $mk   = $tugas->mataKuliah;

        abort_unless($user->isMahasiswa() && $mk->canBeAccessedBy($user), 403, 'Hanya mahasiswa terdaftar yang dapat mengumpulkan tugas.');

        if ($tugas->isBelumDibuka()) {
            return back()->withErrors(['file' => 'Tugas belum dibuka.']);
        }
        if ($tugas->isLewatDeadline()) {
            return back()->withErrors(['file' => 'Batas waktu pengumpulan sudah lewat.']);
        }

        $existing = $tugas->pengumpulan()->where('mahasiswa_id', $user->id)->first();
        if ($existing?->nilai !== null) {
            return back()->withErrors(['file' => 'Tugas sudah dinilai dan tidak dapat diubah.']);
        }

        $request->validate([
            'file'    => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,zip,rar,ppt,pptx,xls,xlsx,txt,png,jpg,jpeg'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'file.required' => 'Pilih file tugas yang akan diunggah.',
            'file.max'      => 'Ukuran file maksimal 50 MB.',
            'file.mimes'    => 'Format file tidak didukung.',
        ]);

        if ($existing) {
            Storage::disk('local')->delete($existing->file_tugas);
        }

        $file = $request->file('file');
        PengumpulanTugas::updateOrCreate(
            ['tugas_id' => $tugas->id, 'mahasiswa_id' => $user->id],
            [
                'file_tugas'   => $file->store("pengumpulan/{$tugas->id}", 'local'),
                'nama_file'    => $file->getClientOriginalName(),
                'catatan'      => $request->input('catatan'),
                'waktu_kumpul' => now(),
            ]
        );

        $mk->recalculateProgress($user);

        return back()->with('success', $existing ? 'Pengumpulan tugas berhasil diperbarui.' : 'Tugas berhasil dikumpulkan.');
    }

    public function download(PengumpulanTugas $pengumpulan): StreamedResponse
    {
        $user = Auth::user();
        $allowed = (int) $pengumpulan->mahasiswa_id === (int) $user->id
            || $pengumpulan->tugas->mataKuliah->canBeManagedBy($user);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($pengumpulan->file_tugas), 404);

        return Storage::disk('local')->download($pengumpulan->file_tugas, $pengumpulan->nama_file);
    }

    public function nilai(Request $request, PengumpulanTugas $pengumpulan): RedirectResponse
    {
        abort_unless($pengumpulan->tugas->mataKuliah->canBeManagedBy(Auth::user()), 403, 'Hanya dosen pengampu yang dapat memberi nilai.');

        $data = $request->validate([
            'nilai'    => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $pengumpulan->update($data);

        return back()->with('success', "Nilai untuk {$pengumpulan->mahasiswa->name} berhasil disimpan.");
    }
}
