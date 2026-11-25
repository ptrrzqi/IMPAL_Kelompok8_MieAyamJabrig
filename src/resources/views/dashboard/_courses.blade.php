{{-- P002_HalamanMainCourse (FR-04): daftar mata kuliah + pencarian, filter, urutan --}}
<div class="col-12 col-lg-8" id="mata-kuliah">
    <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: #1e293b;">
                    {{ $user->isDosen() ? 'Mata Kuliah yang Diampu' : ($user->isAdmin() ? 'Semua Mata Kuliah' : 'Course Overview') }}
                </h5>
                <span class="badge text-secondary border bg-light small rounded-pill">{{ $courses->count() }} mata kuliah</span>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="row g-2 mb-3">
                <div class="col-12">
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama atau kode mata kuliah...">
                </div>
                <div class="col-6">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="semua" @selected($status === 'semua')>Semua status</option>
                        <option value="aktif" @selected($status === 'aktif')>Sedang berjalan</option>
                        <option value="selesai" @selected($status === 'selesai')>Selesai</option>
                    </select>
                </div>
                <div class="col-6">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="nama_asc" @selected($sort === 'nama_asc')>Nama A-Z</option>
                        <option value="nama_desc" @selected($sort === 'nama_desc')>Nama Z-A</option>
                        <option value="kode" @selected($sort === 'kode')>Kode MK</option>
                    </select>
                </div>
            </form>

            <div class="d-flex flex-column gap-3">
                @forelse($courses as $course)
                    @php
                        $selesai = $user->isMahasiswa() ? $course->status_course === 'selesai' : ! $course->status_aktif;
                    @endphp
                    <a href="{{ route('matakuliah.show', $course) }}" class="card border border-light-subtle rounded-3 p-3 text-decoration-none hover-shadow">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width: 48px; height: 48px; background-color: {{ $course->warna }}; font-size: 0.75rem;">
                                    {{ substr($course->kode_mk, 0, 3) }}
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.7rem;">{{ $course->kode_mk }}</span>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $course->semester }}</span>
                                        @if($selesai)
                                            <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 0.7rem;">Selesai</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold mb-1 mt-1 text-dark">{{ $course->nama_mk }}</h6>
                                    <div class="text-muted small">
                                        Dosen: {{ $course->dosen->name ?? '-' }} &bull;
                                        {{ $course->materi_count }} materi &bull; {{ $course->tugas_count }} tugas
                                        @unless($user->isMahasiswa()) &bull; {{ $course->mahasiswa_count }} mahasiswa @endunless
                                    </div>
                                </div>
                            </div>
                            @if($user->isMahasiswa())
                                <div class="text-md-end pt-2 pt-md-0" style="min-width: 140px;">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progress</span>
                                        <span class="fw-bold text-dark">{{ $course->progress }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $course->progress }}%; background-color: {{ $course->warna }};"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    @if($totalTerdaftar === 0)
                        {{-- P002 Tidak Terdaftar --}}
                        <div class="text-center py-5">
                            <div class="fs-1 mb-2">&#128276;</div>
                            <h6 class="fw-bold">Tidak Terdaftar</h6>
                            <p class="text-muted small mb-0">Anda belum terdaftar di mata kuliah manapun.</p>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">Tidak ada mata kuliah yang cocok dengan pencarian/filter.</div>
                    @endif
                @endforelse
            </div>
        </div>
    </div>
</div>
