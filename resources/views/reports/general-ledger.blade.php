@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Laporan Jurnal Umum</h2>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('reports.general-ledger') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date', now()->endOfMonth()->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="account_id" class="form-label">Filter Akun</label>
                        <select name="account_id" id="account_id" class="form-select">
                            <option value="">-- Semua Akun --</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->account_id }}" {{ request('account_id') == $account->account_id ? 'selected' : '' }}>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                     <div class="col-md-2">
                        <label for="journal_group_id" class="form-label">Grup Jurnal / Ref</label>
                        <input type="text" name="journal_group_id" id="journal_group_id" class="form-control" value="{{ request('journal_group_id') }}" placeholder="e.g., INV-...">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL JURNAL UMUM --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Grup Jurnal</th>
                            <th>Referensi</th>
                            <th>No. Akun</th>
                            <th>Nama Akun</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Debit (Rp)</th>
                            <th class="text-end">Kredit (Rp)</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentGroup = null;
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp
                        @forelse ($journalEntries as $entry)
                            @php
                                $totalDebit += $entry->debit;
                                $totalCredit += $entry->credit;
                                
                                // Cek jika ini grup baru untuk styling
                                $isNewGroup = $currentGroup !== $entry->journal_group_id;
                                $currentGroup = $entry->journal_group_id;
                            @endphp
                            <tr style="{{ $isNewGroup ? 'border-top: 2px solid #aaa;' : '' }}">
                                <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                <td class="fw-semibold">{{ $entry->journal_group_id }}</td>
                                <td>
                                    @if ($entry->reference)
                                        {{-- Nanti kita bisa tambahkan link di sini --}}
                                        {{ Str::afterLast($entry->reference_type, '\\') }} #{{ $entry->reference_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $entry->account->account_number ?? 'N/A' }}</td>
                                <td>{{ $entry->account->account_name ?? 'N/A' }}</td>
                                <td>{{ $entry->description }}</td>
                                <td class="text-end font-monospace">{{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}</td>
                                <td class="text-end font-monospace">{{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}</td>
                                <td>{{ $entry->user->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Tidak ada data jurnal untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="6" class="text-end fw-bold">TOTAL PADA HALAMAN INI</td>
                            <td class="text-end fw-bold fs-6">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold fs-6">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $journalEntries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection