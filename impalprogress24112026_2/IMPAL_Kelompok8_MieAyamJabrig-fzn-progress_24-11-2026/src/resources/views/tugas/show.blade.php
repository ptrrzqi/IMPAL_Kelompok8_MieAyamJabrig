@extends('layouts.app')

@section('title', $tugas->judul_tugas)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-danger">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('matakuliah.show', $mk) }}" class="text-danger">{{ $mk->kode_mk }}</a></li>
        <li class="breadcrumb-item active">Minggu {{ $tugas->minggu_ke }}</li>
    </ol>
</nav>

<div class="row g-4">
    {{-- P006: detail tugas --}}
    <div class="col-lg-{{ $canManage ? '12' : '7' }}">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="badge bg-warning-subtle text-warning-emphasis mb-2">Tugas &bull; {{ $tugas->status_label }}</span>
                        <h4 class="fw-bold">{{ $tugas->judul_tugas }}</h4>
                    </div>
                    @if($canManage)
                        <form method="POST" action="{{ route('tugas.destroy', $tugas) }}" onsubmit="return confirm('Hapus tugas ini beserta semua pengumpulannya?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rounded-pill">Hapus Tugas</button>
                        </form>
                    @endif
                </div>
                <table class="table table-sm small mt-2 mb-3" style="max-width: 480px;">
                    <tr><th class="text-muted fw-normal">Dibuka</th><td>{{ $tugas->waktu_buka->translatedFormat('l, d F Y H:i') }}</td></tr>
                    <tr><th class="text-muted fw-normal">Deadline</th><td class="fw-semibold">{{ $tugas->deadline->translatedFormat('l, d F Y H:i') }}</td></tr>
                    <tr><th class="text-muted fw-normal">Sisa waktu</th><td>{{ $tugas->isLewatDeadline() ? 'Sudah lewat' : $tugas->deadline->diffForHumans() }}</td></tr>
                </table>
                <p style="white-space: pre-line;">{{ $tugas->deskripsi }}</p>
                @if($tugas->file_lampiran)
                    <a href="{{ route('tugas.lampiran', $tugas) }}" class="btn btn-sm btn-outline-secondary rounded-pill">Unduh Lampiran: {{ $tugas->nama_lampiran }}</a>
                @endif
            </div>
        </div>
    </div>

    @unless($canManage)
        {{-- Status & form submission mahasiswa --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Status Pengumpulan</h6>
                    @if($submission)
                        <table class="table table-sm small">
                            <tr><th class="text-muted fw-normal">Status</th><td><span class="badge bg-success-subtle text-success">Sudah dikumpulkan</span>
                                @if($submission->isTerlambat())<span class="badge bg-danger-subtle text-danger">Terlambat</span>@endif</td></tr>
                            <tr><th class="text-muted fw-normal">Waktu</th><td>{{ $submission->waktu_kumpul->translatedFormat('d M Y, H:i') }}</td></tr>
                            <tr><th class="text-muted fw-normal">File</th><td><a href="{{ route('pengumpulan.download', $submission) }}" class="text-danger">{{ $submission->nama_file }}</a></td></tr>
                            <tr><th class="text-muted fw-normal">Nilai</th><td class="fw-bold">{{ is_null($submission->nilai) ? 'Belum dinilai' : rtrim(rtrim($submission->nilai, '0'), '.') . ' / 100' }}</td></tr>
                            @if($submission->feedback)
                                <tr><th class="text-muted fw-normal">Feedback</th><td>{{ $submission->feedback }}</td></tr>
                            @endif
                        </table>
                    @else
                        <p class="text-muted small">Anda belum mengumpulkan tugas ini.</p>
                    @endif

                    @if(Auth::user()->isMahasiswa() && ! $tugas->isBelumDibuka() && ! $tugas->isLewatDeadline() && is_null($submission?->nilai))
                        <form method="POST" action="{{ route('pengumpulan.store', $tugas) }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">{{ $submission ? 'Ganti file tugas' : 'File tugas' }} (maks. 50 MB)</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Catatan (opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2">{{ $submission?->catatan }}</textarea>
                            </div>
                            <button class="btn btn-danger w-100 rounded-pill fw-semibold">{{ $submission ? 'Perbarui Submission' : 'Add Submission' }}</button>
                        </form>
                    @elseif($tugas->isBelumDibuka())
                        <div class="alert alert-secondary small mb-0">Tugas belum dibuka.</div>
                    @elseif($tugas->isLewatDeadline() && ! $submission)
                        <div class="alert alert-danger small mb-0">Batas waktu pengumpulan sudah lewat.</div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- Dosen: daftar pengumpulan & penilaian --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Pengumpulan Mahasiswa ({{ $daftar->whereNotNull('pengumpulan')->count() }}/{{ $daftar->count() }})</h6>
                    <div class="table-responsive">
                        <table class="table align-middle small">
                            <thead><tr><th>Mahasiswa</th><th>Status</th><th>File</th><th style="width: 360px;">Nilai & Feedback</th></tr></thead>
                            <tbody>
                            @forelse($daftar as $row)
                                @php $p = $row['pengumpulan']; @endphp
                                <tr>
                                    <td>{{ $row['mahasiswa']->name }}<br><span class="text-muted">{{ $row['mahasiswa']->username }}</span></td>
                                    <td>
                                        @if($p)
                                            <span class="badge bg-success-subtle text-success">Dikumpulkan</span>
                                            @if($p->isTerlambat())<span class="badge bg-danger-subtle text-danger">Terlambat</span>@endif
                                            <div class="text-muted">{{ $p->waktu_kumpul->translatedFormat('d M Y, H:i') }}</div>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                        @endif
                                    </td>
                                    <td>@if($p)<a href="{{ route('pengumpulan.download', $p) }}" class="text-danger">{{ $p->nama_file }}</a>@else - @endif</td>
                                    <td>
                                        @if($p)
                                            <form method="POST" action="{{ route('pengumpulan.nilai', $p) }}" class="d-flex gap-2">
                                                @csrf @method('PATCH')
                                                <input type="number" name="nilai" min="0" max="100" step="0.01" class="form-control form-control-sm" style="width: 80px;" value="{{ $p->nilai }}" placeholder="0-100" required>
                                                <input type="text" name="feedback" class="form-control form-control-sm" value="{{ $p->feedback }}" placeholder="Feedback">
                                                <button class="btn btn-sm btn-danger">Simpan</button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Belum ada mahasiswa terdaftar.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endunless
</div>
@endsection
