@extends('layouts.app')

@section('title', 'Tidak Terdaftar')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 text-center p-5">
            <div class="fs-1 mb-3">&#128276;</div>
            <h4 class="fw-bold">Tidak Terdaftar</h4>
            <p class="text-muted">
                Anda belum terdaftar pada mata kuliah <strong>{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</strong>,
                sehingga materi dan tugas tidak dapat diakses.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-danger rounded-pill px-4">Kembali ke Course Overview</a>
        </div>
    </div>
</div>
@endsection
