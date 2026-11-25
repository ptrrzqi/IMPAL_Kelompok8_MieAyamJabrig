@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4">
    <!-- Welcome Hero Banner -->
    <div class="col-12">
        <div class="p-4 p-md-5 rounded-4 text-white shadow-sm position-relative overflow-hidden"
             style="background: linear-gradient(135deg, #ed1e28 0%, #b8141d 55%, #74080d 100%);">
            <!-- Decorative SVG Circles -->
            <svg class="position-absolute end-0 top-0 h-100 opacity-25 d-none d-md-block" style="pointer-events: none;" viewBox="0 0 400 400">
                <circle cx="300" cy="200" r="180" stroke="white" stroke-width="2" fill="none" />
                <circle cx="300" cy="200" r="120" stroke="white" stroke-width="1.5" fill="none" />
                <circle cx="300" cy="200" r="60" stroke="white" stroke-width="1" fill="none" />
            </svg>

            <div class="position-relative" style="z-index: 1; max-width: 700px;">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill text-white mb-3" style="background-color: rgba(255,255,255,0.2); font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                    <span class="rounded-circle bg-white" style="width: 6px; height: 6px;"></span>
                    SESI TERAUTENTIKASI SEBAGAI {{ strtoupper($user->role_badge) }}
                </div>
                <h1 class="display-6 fw-bold mb-2">Selamat Datang, {{ $user->name }}!</h1>
                <p class="lead mb-0 text-white text-opacity-90" style="font-size: 1rem;">
                    Portal pembelajaran digital CeLOE LMS Telkom University. Akses materi perkuliahan, forum diskusi, dan tugas akademik Anda di sini.
                </p>
            </div>
        </div>
    </div>

    <!-- User Profile & Academic Identity Info -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #1e293b;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ed1e28" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Informasi Akun
                </h5>

                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                    <div class="rounded-4 text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm"
                         style="width: 60px; height: 60px; background: linear-gradient(135deg, #ed1e28, #9b0f15);">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                        <span class="badge rounded-pill fw-semibold mt-1" style="background-color: #fee2e2; color: #ed1e28;">
                            {{ $user->role_badge }}
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2.5 small">
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted">{{ $user->identifier_label }}</span>
                        <span class="fw-semibold text-dark">{{ $user->username }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted">Email SSO</span>
                        <span class="fw-semibold text-dark text-truncate" style="max-width: 180px;" title="{{ $user->email }}">{{ $user->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted">Program Studi</span>
                        <span class="fw-semibold text-dark">{{ $user->prodi ?? 'Telkom University' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted">Status Akun</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif &bull; Terverifikasi</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Waktu Masuk</span>
                        <span class="fw-medium text-dark">{{ now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Quick Logout Action -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Keluar dari Akun (Logout)
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('dashboard._courses')
</div>
@endsection
