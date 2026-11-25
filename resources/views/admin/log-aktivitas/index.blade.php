@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('page-title', 'Log Aktivitas')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Log Aktivitas</h1>
    <p class="text-muted mb-0">Riwayat aktivitas penting yang dilakukan pengguna di aplikasi.</p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aktivitas</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                        <td>{{ $log->user->username ?? 'Pengguna dihapus' }}</td>
                        <td>{{ $log->aktivitas }}</td>
                        <td>{{ $log->deskripsi ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">Belum ada aktivitas yang tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">
        {{ $logs->links() }}
    </div>
</div>
@endsection
