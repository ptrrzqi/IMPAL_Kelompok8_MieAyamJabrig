@extends('layouts.app')

@section('title', $mataKuliah->nama_mk)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-danger">Dashboard</a></li>
        <li class="breadcrumb-item active">{{ $mataKuliah->kode_mk }}</li>
    </ol>
</nav>

{{-- Header mata kuliah --}}
<div class="p-4 rounded-4 text-white shadow-sm mb-4" style="background: linear-gradient(135deg, {{ $mataKuliah->warna }} 0%, #18212f 100%);">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
        <div>
            <span class="badge bg-white text-dark rounded-pill mb-2">{{ $mataKuliah->kode_mk }} &bull; {{ $mataKuliah->semester }}</span>
            <h2 class="fw-bold mb-1">{{ $mataKuliah->nama_mk }}</h2>
            <div class="small opacity-75">Dosen Pengampu: {{ $mataKuliah->dosen->name ?? '-' }}</div>
            @if($mataKuliah->deskripsi)
                <p class="mb-0 mt-2 small" style="max-width: 720px;">{{ $mataKuliah->deskripsi }}</p>
            @endif
        </div>
        <div class="text-md-end">
            @if(! is_null($progress))
                <div class="small opacity-75">Progress Anda</div>
                <div class="display-6 fw-bold">{{ $progress }}%</div>
            @endif
            @if($canManage)
                <div class="d-flex gap-2 mt-2 justify-content-md-end">
                    <button class="btn btn-light btn-sm rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalMateri">+ Tambah Materi</button>
                    <button class="btn btn-outline-light btn-sm rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTugas">+ Tambah Tugas</button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- P003 / P005: konten per minggu --}}
    <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
        @forelse($minggu as $m)
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h6 class="fw-bold mb-0">Minggu {{ $m }}</h6>
                </div>
                <div class="card-body px-4 pt-2">
                    <ul class="list-group list-group-flush">
                        @foreach($mataKuliah->materi->where('minggu_ke', $m) as $materi)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary me-2">Materi</span>
                                    <span class="fw-semibold">{{ $materi->judul_materi }}</span>
                                    @if($materi->deskripsi)<div class="text-muted small ms-1">{{ $materi->deskripsi }}</div>@endif
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    @if($materi->file_path)
                                        <a href="{{ route('materi.download', $materi) }}" class="btn btn-sm btn-outline-secondary rounded-pill">Unduh</a>
                                    @endif
                                    @if($canManage)
                                        <form method="POST" action="{{ route('materi.destroy', $materi) }}" onsubmit="return confirm('Hapus materi ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger rounded-pill">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                        @foreach($tugas->where('minggu_ke', $m) as $t)
                            @php $sub = $t->pengumpulan->first(); @endphp
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="badge bg-warning-subtle text-warning-emphasis me-2">Tugas</span>
                                    <a href="{{ route('tugas.show', $t) }}" class="fw-semibold text-dark">{{ $t->judul_tugas }}</a>
                                    <div class="text-muted small ms-1">
                                        Deadline: {{ $t->deadline->translatedFormat('d M Y, H:i') }} &bull; {{ $t->status_label }}
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    @if($canManage)
                                        <span class="badge bg-light text-dark border">{{ $t->pengumpulan_count }}/{{ $mahasiswa->count() }} terkumpul</span>
                                    @elseif($sub)
                                        <span class="badge bg-success-subtle text-success">
                                            {{ is_null($sub->nilai) ? 'Sudah dikumpulkan' : 'Nilai: ' . rtrim(rtrim($sub->nilai, '0'), '.') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum dikumpulkan</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">Belum ada materi atau tugas pada mata kuliah ini.</div>
        @endforelse
    </div>

    @if($canManage)
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Peserta ({{ $mahasiswa->count() }} mahasiswa)</h6>
                    <ul class="list-unstyled small mb-0">
                        @forelse($mahasiswa as $mhs)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span>{{ $mhs->name }}<br><span class="text-muted">{{ $mhs->username }}</span></span>
                                <span class="fw-semibold">{{ $mhs->pivot->progress }}%</span>
                            </li>
                        @empty
                            <li class="text-muted">Belum ada peserta.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if($canManage)
@section('modals')
{{-- P004: Upload materi --}}
<div class="modal fade" id="modalMateri" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('materi.store', $mataKuliah) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Materi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Minggu ke-</label><input type="number" name="minggu_ke" min="1" max="16" class="form-control" value="{{ ($minggu->max() ?? 0) + 1 }}" required></div>
                <div class="mb-3"><label class="form-label">Judul Materi</label><input type="text" name="judul_materi" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
                <div class="mb-1"><label class="form-label">File (PDF/PPT/DOC/ZIP, maks. 50 MB)</label><input type="file" name="file" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button class="btn btn-danger rounded-pill px-4">Simpan Materi</button></div>
        </form>
    </div>
</div>

{{-- P004: Upload tugas --}}
<div class="modal fade" id="modalTugas" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('tugas.store', $mataKuliah) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Tugas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Minggu ke-</label><input type="number" name="minggu_ke" min="1" max="16" class="form-control" value="{{ ($minggu->max() ?? 0) + 1 }}" required></div>
                <div class="mb-3"><label class="form-label">Judul Tugas</label><input type="text" name="judul_tugas" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label">Dibuka</label><input type="datetime-local" name="waktu_buka" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                    <div class="col"><label class="form-label">Deadline</label><input type="datetime-local" name="deadline" class="form-control" value="{{ now()->addWeek()->format('Y-m-d\TH:i') }}" required></div>
                </div>
                <div class="mb-1"><label class="form-label">Lampiran (opsional)</label><input type="file" name="lampiran" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-danger rounded-pill px-4">Simpan Tugas</button></div>
        </form>
    </div>
</div>
@endsection
@endif
