@extends('admin.layouts.app')

@section('title', 'Detail Klien')

@section('content')
<div x-data="clientTabs('{{ route('admin.clients.tab-content', $client->client_id) }}')">
    
    {{-- HEADER & GLOBAL ACTIONS --}}
    <div class="page-header flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="page-title">Detail Klien</h1>
            <p class="page-subtitle">Dashboard aktivitas dan profil: <strong>{{ $client->client_name }}</strong></p>
        </div>

        {{-- Toolbar Tombol Aksi --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-secondary">
                <i class="material-icons text-[16px]">arrow_back</i> Kembali
            </a>
            
            <a href="{{ route('admin.clients.edit', $client->client_id) }}" class="btn btn-sm btn-secondary">
                <i class="material-icons text-[16px] text-indigo-600">edit</i> Edit
            </a>

            {{-- WhatsApp --}}
            @if($client->phone_number)
                @php $wa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $client->phone_number)); @endphp
                <a href="https://wa.me/{{ $wa }}" target="_blank" class="btn btn-sm btn-success text-white">
                    <i class="material-icons text-[16px]">chat</i> WA
                </a>
            @endif

            {{-- Lock/Unlock --}}
            @if($client->is_locked)
                <form action="{{ route('admin.clients.unlock', $client->client_id) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-warning text-white">
                        <i class="material-icons text-[16px]">lock_open</i> Buka Kunci
                    </button>
                </form>
            @else
                <form action="{{ route('admin.clients.lock', $client->client_id) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="button" class="btn btn-sm btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50"
                            onclick="confirmDialog({title: 'Kunci Akun?', text: 'Klien tidak akan bisa bertransaksi.', icon: 'warning', confirmText: 'Kunci', confirmColor: 'warning'}).then(res => { if(res.isConfirmed) this.closest('form').submit() })">
                        <i class="material-icons text-[16px]">lock</i>
                    </button>
                </form>
            @endif

            {{-- Approval --}}
            @if(!$client->is_approved)
                <form action="{{ route('admin.clients.approve', $client->client_id) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="material-icons text-[16px]">check_circle</i> Setujui
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- TOP SECTION: PROFILE & STATS --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 mb-8">
        
        {{-- KOLOM 1 (KIRI): INFORMASI KLIEN (1/4 Width) --}}
        <div class="xl:col-span-1 space-y-6">
            <div class="card h-full">
                {{-- Cover / Header Card --}}
                <div class="p-6 flex flex-col items-center text-center border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                    <img src="{{ $client->avatar_url }}" alt="Avatar" class="w-20 h-20 rounded-full border-4 border-white dark:border-slate-600 shadow-md mb-3 object-cover">
                    
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">
                        {{ $client->client_name }}
                    </h2>
                    
                    <div class="flex flex-wrap justify-center gap-2 mt-3">
                        @if($client->is_locked)
                            <span class="badge badge-danger">Terkunci</span>
                        @elseif(!$client->is_approved)
                            <span class="badge badge-warning">Menunggu Approval</span>
                        @else
                            <span class="badge badge-success">Verified</span>
                        @endif
                        
                        @if($client->google_id)
                            <span class="badge bg-blue-50 text-blue-600 border border-blue-200">Linked</span>
                        @endif
                    </div>
                </div>

                {{-- Detail List --}}
                <div class="p-5 space-y-3">
                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
                            <i class="material-icons text-[16px]">person</i>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">PIC</span>
                            {{ $client->person_in_charge ?? '-' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                            <i class="material-icons text-[16px]">email</i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Email</span>
                            <span class="truncate block">{{ $client->email ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                            <i class="material-icons text-[16px]">call</i>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Telepon</span>
                            {{ $client->phone_number ?? '-' }}
                        </div>
                    </div>

                    <div class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300 pt-2 border-t border-slate-100 dark:border-slate-700">
                        <div class="w-8 h-8 rounded bg-rose-50 flex items-center justify-center text-rose-600 shrink-0">
                            <i class="material-icons text-[16px]">place</i>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Alamat</span>
                            <span class="leading-snug text-xs">{{ $client->address ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM 2 (KANAN): STATISTIK KEUANGAN (3/4 Width) --}}
        <div class="xl:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 h-full content-start">
                
                {{-- 1. Saldo Deposit --}}
                <div class="card p-4 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="absolute right-2 top-2 text-emerald-100 dark:text-emerald-900/20 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-6xl">account_balance_wallet</i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Saldo Deposit</p>
                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 truncate">
                            Rp {{ number_format($client->balance, 0, ',', '.') }}
                        </p>
                        <span class="text-[10px] text-slate-400">Siap digunakan</span>
                    </div>
                </div>

                {{-- 2. Saldo Tertahan --}}
                <div class="card p-4 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="absolute right-2 top-2 text-amber-100 dark:text-amber-900/20 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-6xl">pending</i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Saldo Tertahan</p>
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1 truncate">
                            Rp {{ number_format($client->pending_balance, 0, ',', '.') }}
                        </p>
                        <span class="text-[10px] text-slate-400">Menunggu Verifikasi</span>
                    </div>
                </div>

                {{-- 3. Tagihan (AR) --}}
                @php
                    $unpaid = $client->salesInvoices->whereIn('status', ['unpaid', 'partially_paid']);
                    $totalAR = $unpaid->sum(fn($i) => $i->remaining_balance ?? 0);
                @endphp
                <div class="card p-4 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="absolute right-2 top-2 text-rose-100 dark:text-rose-900/20 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-6xl">money_off</i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Sisa Tagihan (AR)</p>
                        <p class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1 truncate">
                            Rp {{ number_format($totalAR, 0, ',', '.') }}
                        </p>
                        <span class="text-[10px] text-slate-400">{{ $unpaid->count() }} Invoice Belum Lunas</span>
                    </div>
                </div>

                {{-- 4. Total Retur --}}
                @php $totalRet = $client->salesInvoices->sum(fn($i) => $i->returns->sum('total_amount')); @endphp
                <div class="card p-4 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="absolute right-2 top-2 text-indigo-100 dark:text-indigo-900/20 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-6xl">assignment_return</i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Total Retur</p>
                        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1 truncate">
                            Rp {{ number_format($totalRet, 0, ',', '.') }}
                        </p>
                        <span class="text-[10px] text-slate-400">Akumulasi Nilai</span>
                    </div>
                </div>
                
                {{-- Grafik / Info Tambahan (Opsional, agar area kanan tidak kosong jika height kiri tinggi) --}}
                <div class="col-span-full mt-2">
                    <div class="card bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Ringkasan Aktivitas</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                            <div>
                                <span class="block text-2xl font-bold text-slate-700 dark:text-slate-200">{{ $client->salesInvoices->count() }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Total Invoice</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-bold text-emerald-600">{{ $client->salesInvoices->where('status', 'paid')->count() }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Lunas</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-bold text-rose-600">{{ $unpaid->count() }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Menunggak</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-bold text-indigo-600">{{ $client->salesInvoices->sum(fn($i)=>$i->returns->count()) }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Kali Retur</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOTTOM SECTION: DATA TABS (FULL WIDTH) --}}
    {{-- Ini sekarang berada di luar grid kolom, jadi memakan lebar penuh (full width) --}}
    <div class="card bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700">
        
        {{-- Custom Pill Navigation --}}
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            
            <nav class="flex flex-wrap gap-2 p-1 bg-slate-100 dark:bg-slate-900/50 rounded-xl w-full sm:w-auto" aria-label="Tabs">
                <button @click="loadTab('ledger')" 
                        :class="activeTab === 'ledger' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 flex-1 sm:flex-none justify-center">
                    <i class="material-icons text-[16px]">menu_book</i> Buku Besar
                </button>

                <button @click="loadTab('invoices')" 
                        :class="activeTab === 'invoices' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 flex-1 sm:flex-none justify-center">
                    <i class="material-icons text-[16px]">receipt_long</i> Invoice
                </button>

                <button @click="loadTab('returns')" 
                        :class="activeTab === 'returns' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 flex-1 sm:flex-none justify-center">
                    <i class="material-icons text-[16px]">assignment_return</i> Retur
                </button>

                <button @click="loadTab('adjustments')" 
                        :class="activeTab === 'adjustments' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 flex-1 sm:flex-none justify-center">
                    <i class="material-icons text-[16px]">tune</i> Penyesuaian
                </button>
            </nav>

            {{-- Label Info (Optional) --}}
            <div class="text-xs text-slate-400 hidden sm:block">
                Menampilkan data riwayat transaksi
            </div>
        </div>

        {{-- Tab Content (Dynamic AJAX) --}}
        <div class="card-body p-0 relative min-h-[400px]">
            {{-- Loading Spinner --}}
            <div x-show="isLoading" class="absolute inset-0 bg-white/80 dark:bg-slate-800/80 z-20 flex flex-col items-center justify-center backdrop-blur-[1px] transition-all duration-300">
                <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Memuat Data...</span>
            </div>

            {{-- Content Container --}}
            <div x-html="tabContent" class="animate-fade-in p-0"></div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function clientTabs(baseUrl) {
        return {
            activeTab: 'ledger',
            tabContent: '',
            isLoading: false,

            init() {
                this.loadTab('ledger');
            },

            loadTab(tabName) {
                // Prevent reload if clicking active tab
                if (this.activeTab === tabName && this.tabContent !== '') return;

                this.activeTab = tabName;
                this.isLoading = true;

                fetch(`${baseUrl}?tab=${tabName}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat data');
                        return response.text();
                    })
                    .then(html => {
                        setTimeout(() => {
                            this.tabContent = html;
                            this.isLoading = false;
                        }, 200);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.tabContent = `<div class="flex flex-col items-center justify-center h-64 text-rose-500">
                            <i class="material-icons text-4xl mb-2">wifi_off</i>
                            <p class="text-sm font-medium">Gagal memuat data. Periksa koneksi Anda.</p>
                            <button @click="loadTab('${tabName}')" class="btn btn-sm btn-secondary mt-4">Coba Lagi</button>
                        </div>`;
                        this.isLoading = false;
                    });
            }
        }
    }
</script>
@endpush
@endsection