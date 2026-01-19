@extends('admin.layouts.app')

@section('title', 'Jejak Audit Sistem')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Jejak Audit (Audit Logs)</h1>
            <p class="page-subtitle">Rekaman aktivitas, perubahan data, dan keamanan sistem secara real-time</p>
        </div>
        <div>
            <span class="badge bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded-lg flex items-center gap-2">
                <i class="material-icons text-[16px]">history</i>
                Retensi Log: 90 Hari
            </span>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-6 border-l-4 border-indigo-500 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.audit-logs.index') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    {{-- Filter User --}}
                    <div>
                        <label class="form-label">Aktor / User</label>
                        <select name="user_id" class="tom-select" placeholder="Pilih User...">
                            <option value="">Semua User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->user_id }}" {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                    {{ $user->full_name }} ({{ $user->role ?? 'Staff' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Modul --}}
                    <div>
                        <label class="form-label">Modul (Subject)</label>
                        <select name="subject_type" class="tom-select" placeholder="Pilih Modul...">
                            <option value="">Semua Modul</option>
                            @foreach($subjects as $modelClass => $label)
                                <option value="{{ $modelClass }}" {{ request('subject_type') == $modelClass ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Aktivitas --}}
                    <div>
                        <label class="form-label">Jenis Aktivitas</label>
                        <select name="action" class="form-select">
                            <option value="">Semua Aktivitas</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created (Buat Baru)</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated (Edit)</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted (Hapus)</option>
                            <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Login System</option>
                            <option value="restored" {{ request('action') == 'restored' ? 'selected' : '' }}>Restore</option>
                        </select>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary w-full shadow-md shadow-indigo-500/20">
                            <i class="material-icons text-[18px]">search</i> Filter
                        </button>
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary w-12 justify-center" title="Reset Filter">
                            <i class="material-icons">refresh</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table with Alpine Modal Data --}}
    <div class="card card-plain" x-data="auditLogViewer()">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-48">Waktu & IP</th>
                        <th>Aktor (Pelaku)</th>
                        <th>Aktivitas</th>
                        <th>Objek Data</th>
                        <th>Keterangan</th>
                        <th class="w-24 text-right">Raw Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            {{-- Waktu --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-xs">
                                        {{ $log->created_at->format('d M Y') }}
                                    </span>
                                    <span class="font-mono text-slate-500 text-[10px]">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </span>
                                    <div class="flex items-center gap-1 mt-1.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[10px] font-mono text-slate-500 dark:text-slate-400">
                                            {{ $log->ip_address ?? 'Unknown IP' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Aktor --}}
                            <td>
                                @if($log->user)
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-sm
                                            {{ $log->user_type === 'client' ? 'bg-emerald-500' : 'bg-indigo-600' }}">
                                            @if($log->user_type === 'client')
                                                {{ substr($log->user->client_name ?? 'C', 0, 1) }}
                                            @else
                                                {{ substr($log->user->full_name ?? 'A', 0, 1) }}
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-tight">
                                                @if($log->user_type === 'client')
                                                    {{ $log->user->client_name }}
                                                @else
                                                    {{ $log->user->full_name }}
                                                @endif
                                            </p>
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mt-0.5">
                                                @if($log->user_type === 'client')
                                                    <span class="text-emerald-600 dark:text-emerald-400">Client Portal</span>
                                                @else
                                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $log->user->role ?? 'Staff' }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @elseif($log->user_id)
                                    <div class="flex items-center gap-2 opacity-60">
                                        <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-500">
                                            <i class="material-icons text-[16px]">person_off</i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-500">User Terhapus</p>
                                            <p class="text-[10px] text-slate-400">ID: {{ $log->user_id }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                            <i class="material-icons text-[16px]">smart_toy</i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-600 dark:text-slate-400">System / Bot</p>
                                            <p class="text-[10px] text-slate-400">Automated</p>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Aktivitas Badge --}}
                            <td>
                                @php
                                    $color = 'slate';
                                    $icon = 'info';
                                    
                                    switch($log->action) {
                                        case 'created': $color = 'emerald'; $icon = 'add_circle'; break;
                                        case 'updated': $color = 'amber'; $icon = 'edit'; break;
                                        case 'deleted': $color = 'rose'; $icon = 'delete'; break;
                                        case 'login':   $color = 'blue'; $icon = 'login'; break;
                                        case 'restored': $color = 'indigo'; $icon = 'restore'; break;
                                    }
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide border bg-{{ $color }}-50 text-{{ $color }}-700 border-{{ $color }}-200 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-400 dark:border-{{ $color }}-800">
                                    <i class="material-icons text-[14px]">{{ $icon }}</i> {{ $log->action }}
                                </span>
                            </td>

                            {{-- Objek --}}
                            <td>
                                <div class="flex flex-col">
                                    @php
                                        $modelName = class_basename($log->subject_type);
                                        $readableName = match($modelName) {
                                            'SalesInvoice' => 'Invoice Penjualan',
                                            'PurchaseOrder' => 'Purchase Order',
                                            'Product' => 'Produk',
                                            'Client' => 'Data Klien',
                                            'Supplier' => 'Data Supplier',
                                            'Payment' => 'Pembayaran',
                                            default => $modelName
                                        };
                                    @endphp
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        {{ $readableName }}
                                    </span>
                                    <span class="font-mono text-[10px] text-slate-500 bg-slate-100 dark:bg-slate-700 px-1 rounded w-fit">
                                        ID: #{{ $log->subject_id }}
                                    </span>
                                </div>
                            </td>

                            {{-- Detail --}}
                            <td>
                                <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </p>
                                
                                {{-- Tombol View Visual Changes --}}
                                @if(!empty($log->properties))
                                    <button @click="openModal({{ json_encode($log->properties) }}, '{{ $log->action }}', 'visual')" 
                                            class="mt-1.5 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 group-hover:text-indigo-700">
                                        <i class="material-icons text-[12px]">visibility</i> Lihat Perubahan
                                    </button>
                                @endif
                            </td>

                            {{-- Kolom Aksi RAW JSON --}}
                            <td class="text-right">
                                @if(!empty($log->properties))
                                    <button @click="openModal({{ json_encode($log->properties) }}, '{{ $log->action }}', 'raw')" 
                                            class="btn-action text-slate-500 bg-slate-50 border-slate-200 hover:bg-slate-800 hover:text-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 transition-colors"
                                            title="Lihat Raw JSON Data">
                                        <i class="material-icons text-[16px]">data_object</i>
                                    </button>
                                @else
                                    <span class="text-slate-300 text-xs italic">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3">
                                        <i class="material-icons text-3xl">security</i>
                                    </div>
                                    <p class="text-sm font-medium">Tidak ada log aktivitas yang ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $logs->links('vendor.pagination.admin') }}
        </div>

        {{-- ======================================================= --}}
        {{-- MODAL UNIVERSAL (TELEPORTED TO BODY) --}}
        {{-- ======================================================= --}}
        {{-- Menggunakan x-teleport="body" agar modal keluar dari container table --}}
        {{-- Ini memastikan position: fixed bekerja relatif terhadap layar (viewport) --}}
        <template x-teleport="body">
            <div x-show="isOpen" 
                 style="display: none;"
                 class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col transform transition-all"
                     @click.outside="closeModal()"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 rounded-t-xl">
                        <div class="flex items-center gap-4">
                            <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                <i class="material-icons" x-text="viewMode === 'visual' ? 'difference' : 'data_object'"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white" x-text="viewMode === 'visual' ? 'Visual Perubahan Data' : 'Raw JSON Data'"></h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Detail properti audit log.</p>
                            </div>
                        </div>
                        
                        {{-- Tab Switcher Small --}}
                        <div class="flex bg-slate-200 dark:bg-slate-700 rounded-lg p-1">
                            <button @click="viewMode = 'visual'" 
                                    :class="viewMode === 'visual' ? 'bg-white dark:bg-slate-600 text-indigo-600 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                    class="px-3 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                                <i class="material-icons text-[12px]">visibility</i> Visual
                            </button>
                            <button @click="viewMode = 'raw'" 
                                    :class="viewMode === 'raw' ? 'bg-white dark:bg-slate-600 text-indigo-600 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                    class="px-3 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                                <i class="material-icons text-[12px]">code</i> JSON
                            </button>
                        </div>

                        <button @click="closeModal()" class="ml-4 text-slate-400 hover:text-rose-500 transition-colors">
                            <i class="material-icons">close</i>
                        </button>
                    </div>

                    {{-- Modal Body (Scrollable) --}}
                    <div class="p-0 overflow-y-auto flex-1 custom-scrollbar bg-slate-50/30 dark:bg-slate-900/10">
                        
                        {{-- MODE 1: VISUAL (DIFF VIEWER) --}}
                        <div x-show="viewMode === 'visual'" class="p-6">
                            
                            {{-- UPDATED: Side by Side --}}
                            <template x-if="actionType === 'updated'">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-0 md:gap-6 border rounded-xl overflow-hidden border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                                    {{-- Old --}}
                                    <div class="bg-rose-50/50 dark:bg-rose-900/10">
                                        <div class="px-4 py-2 border-b border-rose-100 dark:border-rose-800/30 bg-rose-100/50 dark:bg-rose-900/20 flex justify-between items-center">
                                            <span class="text-xs font-bold uppercase text-rose-700 dark:text-rose-400 flex items-center gap-1">
                                                <i class="material-icons text-[14px]">remove_circle</i> Data Lama
                                            </span>
                                        </div>
                                        <div class="divide-y divide-rose-100 dark:divide-rose-800/20">
                                            <template x-for="(value, key) in oldData" :key="key">
                                                <div class="px-4 py-3 hover:bg-rose-100/30 transition-colors">
                                                    <p class="text-[10px] font-bold text-rose-400 uppercase tracking-wide mb-1" x-text="key"></p>
                                                    <p class="text-sm font-mono text-rose-800 dark:text-rose-200 break-words" x-text="formatValue(value)"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    {{-- New --}}
                                    <div class="bg-emerald-50/50 dark:bg-emerald-900/10 border-t md:border-t-0 md:border-l border-slate-200 dark:border-slate-700">
                                        <div class="px-4 py-2 border-b border-emerald-100 dark:border-emerald-800/30 bg-emerald-100/50 dark:bg-emerald-900/20 flex justify-between items-center">
                                            <span class="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                                <i class="material-icons text-[14px]">add_circle</i> Data Baru
                                            </span>
                                        </div>
                                        <div class="divide-y divide-emerald-100 dark:divide-emerald-800/20">
                                            <template x-for="(value, key) in attributes" :key="key">
                                                <div class="px-4 py-3 hover:bg-emerald-100/30 transition-colors">
                                                    <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-wide mb-1" x-text="key"></p>
                                                    <p class="text-sm font-mono text-emerald-800 dark:text-emerald-200 break-words" x-text="formatValue(value)"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- CREATED: Single Column --}}
                            <template x-if="actionType === 'created'">
                                <div class="bg-white dark:bg-slate-800 border border-emerald-100 dark:border-emerald-800/30 rounded-xl overflow-hidden shadow-sm">
                                    <div class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800/30">
                                        <span class="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400">Data Baru Dibuat</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4">
                                        <template x-for="(value, key) in attributes" :key="key">
                                            <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1" x-text="key"></p>
                                                <p class="text-sm font-mono text-slate-700 dark:text-slate-200 break-words" x-text="formatValue(value)"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- DELETED: Single Column --}}
                            <template x-if="actionType === 'deleted'">
                                <div class="bg-white dark:bg-slate-800 border border-rose-100 dark:border-rose-800/30 rounded-xl overflow-hidden shadow-sm">
                                    <div class="px-4 py-2 bg-rose-50 dark:bg-rose-900/20 border-b border-rose-100 dark:border-rose-800/30">
                                        <span class="text-xs font-bold uppercase text-rose-700 dark:text-rose-400">Data Yang Dihapus</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4">
                                        <template x-for="(value, key) in oldData" :key="key">
                                            <div class="p-3 rounded-lg border border-rose-100 dark:border-rose-900/20 bg-rose-50/30 dark:bg-rose-900/10">
                                                <p class="text-[10px] font-bold text-rose-400 uppercase mb-1" x-text="key"></p>
                                                <p class="text-sm font-mono text-rose-700 dark:text-rose-200 break-words" x-text="formatValue(value)"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- MODE 2: RAW JSON --}}
                        <div x-show="viewMode === 'raw'" class="p-0 h-full relative">
                            <div class="absolute top-4 right-4 z-10">
                                <button @click="copyToClipboard()" class="text-xs flex items-center gap-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 px-2 py-1 rounded text-slate-600 dark:text-slate-300 transition-colors">
                                    <i class="material-icons text-[14px]">content_copy</i> Copy
                                </button>
                            </div>
                            <pre class="w-full h-full p-6 text-xs font-mono text-slate-300 bg-[#1e1e1e] overflow-auto" x-text="jsonRaw"></pre>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-b-xl flex justify-end">
                        <button @click="closeModal()" class="btn btn-secondary">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function auditLogViewer() {
            return {
                isOpen: false,
                viewMode: 'visual',
                actionType: '',
                oldData: {},
                attributes: {},
                jsonRaw: '',

                openModal(properties, action, mode = 'visual') {
                    this.actionType = action;
                    this.viewMode = mode;
                    this.jsonRaw = JSON.stringify(properties, null, 4);
                    this.attributes = properties.attributes || {};
                    this.oldData = properties.old || {};

                    // Fallback logic
                    if (Object.keys(this.attributes).length === 0 && Object.keys(this.oldData).length === 0) {
                        if (action === 'created') this.attributes = properties;
                        if (action === 'deleted') this.oldData = properties;
                    }

                    this.isOpen = true;
                    document.body.style.overflow = 'hidden'; 
                },

                closeModal() {
                    this.isOpen = false;
                    document.body.style.overflow = 'auto'; 
                    setTimeout(() => {
                        this.oldData = {};
                        this.attributes = {};
                        this.jsonRaw = '';
                    }, 300);
                },

                formatValue(val) {
                    if (val === null) return 'null';
                    if (val === true) return 'true';
                    if (val === false) return 'false';
                    if (typeof val === 'object') return JSON.stringify(val);
                    return val;
                },

                copyToClipboard() {
                    navigator.clipboard.writeText(this.jsonRaw).then(() => {
                        showToast('JSON berhasil disalin', 'success');
                    });
                }
            }
        }
    </script>
    @endpush
@endsection