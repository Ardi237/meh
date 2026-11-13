@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div style="margin-bottom:1.5rem">
    <h1 style="font-size:1.5rem;font-weight:700;color:var(--slate-900)">Kelola Pengguna</h1>
    <p style="color:var(--slate-500)">Admin dapat mengatur peran akun Dosen dan Mahasiswa.</p>
</div>
<div class="card" style="padding:1.25rem;overflow-x:auto">
<table style="width:100%;border-collapse:collapse">
    <thead><tr style="text-align:left;border-bottom:1px solid var(--slate-200)">
        <th style="padding:.75rem">Nama</th><th>Email</th><th>Nomor Induk</th><th>Role</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    @forelse($users as $user)
        <tr style="border-bottom:1px solid var(--slate-200)">
            <td style="padding:.75rem">{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->nomor_induk ?? '-' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.users.role', $user) }}" style="display:flex;gap:.5rem;align-items:center">
                    @csrf @method('PATCH')
                    <select name="role" class="form-control" style="padding:.4rem">
                        @foreach(['mahasiswa'=>'Mahasiswa','dosen'=>'Dosen','admin'=>'Admin'] as $value=>$label)
                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-secondary btn-sm" type="submit">Simpan</button>
                </form>
            </td>
            <td>
                @if($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Yakin ingin menghapus akun ini?">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm" style="background:#fee2e2;color:#991b1b" type="submit">Hapus</button>
                </form>
                @else
                    <span style="color:var(--slate-400)">Akun aktif</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" style="padding:1rem;text-align:center">Belum ada pengguna.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:1rem">{{ $users->links() }}</div>
</div>
@endsection
