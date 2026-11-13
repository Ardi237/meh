@extends('layouts.app')

@section('title', 'Verifikasi Email')

@section('content')
<div class="card" style="max-width: 560px; margin: 2rem auto;">
    <div class="card-header">
        <div>
            <h1 class="card-title">Verifikasi Email</h1>
            <p class="card-subtitle">Buka tautan yang dikirim ke {{ Auth::user()->email }} sebelum mengakses LMS.</p>
        </div>
    </div>
    <div class="card-body">
        <p style="margin-top: 0; color: var(--slate-600);">
            Belum menerima email? Kirim ulang tautan verifikasi melalui tombol berikut.
        </p>
        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Kirim Ulang Email Verifikasi</button>
        </form>
    </div>
</div>
@endsection
