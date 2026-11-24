<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\Materi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Upload/Download controller untuk materi (P004 / P005).
 */
class MateriController extends Controller
{
    public function store(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        abort_unless($mataKuliah->canBeManagedBy(Auth::user()), 403, 'Hanya dosen pengampu yang dapat menambah materi.');

        $data = $request->validate([
            'minggu_ke'    => ['required', 'integer', 'min:1', 'max:16'],
            'judul_materi' => ['required', 'string', 'max:255'],
            'deskripsi'    => ['nullable', 'string', 'max:2000'],
            'file'         => ['required', 'file', 'max:51200', 'mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,zip,rar,txt,png,jpg,jpeg,mp4'],
        ], [
            'file.required' => 'File materi wajib diunggah.',
            'file.max'      => 'Ukuran file maksimal 50 MB.',
            'file.mimes'    => 'Format file tidak didukung.',
        ]);

        $file = $request->file('file');
        $mataKuliah->materi()->create([
            'minggu_ke'    => $data['minggu_ke'],
            'judul_materi' => $data['judul_materi'],
            'deskripsi'    => $data['deskripsi'] ?? null,
            'file_path'    => $file->store("materi/{$mataKuliah->id}", 'local'),
            'file_name'    => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Materi berhasil ditambahkan.');
    }

    public function download(Materi $materi): StreamedResponse
    {
        abort_unless($materi->mataKuliah->canBeAccessedBy(Auth::user()), 403);
        abort_unless($materi->file_path && Storage::disk('local')->exists($materi->file_path), 404, 'File materi tidak ditemukan.');

        return Storage::disk('local')->download($materi->file_path, $materi->file_name);
    }

    public function destroy(Materi $materi): RedirectResponse
    {
        abort_unless($materi->mataKuliah->canBeManagedBy(Auth::user()), 403);

        if ($materi->file_path) {
            Storage::disk('local')->delete($materi->file_path);
        }
        $materi->delete();

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
