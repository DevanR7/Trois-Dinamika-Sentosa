@extends('admin.layouts.app')

@section('title', 'Tambah Akun Baru')

@section('content')
<div class="flex flex-col gap-6" x-data="coaForm()">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.chart-of-accounts.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali ke Daftar
            </a>
            <h1 class="page-title">Tambah Akun Baru</h1>
        </div>
    </div>

    <form action="{{ route('admin.chart-of-accounts.store') }}" method="POST">
        @csrf

        <div class="card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Kiri: Identitas Akun --}}
                <div class="space-y-6">
                    {{-- Parent Account --}}
                    <div>
                        <label for="parent_account_id" class="form-label label-optional">Induk Akun (Parent)</label>
                        <select id="parent_account_id" name="parent_account_id" class="tom-select" placeholder="Pilih Induk (Jika Sub-akun)...">
                            <option value="">-- Tidak Ada (Akun Utama) --</option>
                            @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->account_id }}" {{ old('parent_account_id') == $parent->account_id ? 'selected' : '' }}>
                                    {{ $parent->account_number }} - {{ $parent->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Biarkan kosong jika ini adalah Akun Induk (Header).
                        </p>
                        @error('parent_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Account Number --}}
                    <div>
                        <label for="account_number" class="form-label label-required">Kode Akun (Nomor)</label>
                        <input type="text" id="account_number" name="account_number" 
                               value="{{ old('account_number') }}" 
                               class="form-input font-mono @error('account_number') is-invalid @enderror" 
                               placeholder="Contoh: 1101" required>
                        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Account Name --}}
                    <div>
                        <label for="account_name" class="form-label label-required">Nama Akun</label>
                        <input type="text" id="account_name" name="account_name" 
                               value="{{ old('account_name') }}" 
                               class="form-input @error('account_name') is-invalid @enderror" 
                               placeholder="Contoh: Kas Kecil" required>
                        @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Kanan: Klasifikasi --}}
                <div class="space-y-6">
                    {{-- Account Type --}}
                    <div>
                        <label for="account_type" class="form-label label-required">Kategori Akun</label>
                        <select id="account_type" name="account_type" 
                                class="tom-select @error('account_type') is-invalid @enderror"
                                x-model="selectedType"
                                @change="updateNormalBalance()"
                                required>
                            <option value="">Pilih Kategori...</option>
                            @foreach($accountTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Normal Balance --}}
                    <div>
                        <label for="normal_balance" class="form-label label-required">Posisi Normal (Saldo)</label>
                        <select id="normal_balance" name="normal_balance" 
                                class="form-select @error('normal_balance') is-invalid @enderror"
                                x-model="normalBalance"
                                required>
                            @foreach($normalBalances as $balance)
                                <option value="{{ $balance }}">{{ $balance }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1 italic">
                            Otomatis disarankan berdasarkan kategori, namun dapat diubah manual.
                        </p>
                        @error('normal_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status --}}
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            <span class="ms-3 text-sm font-medium text-slate-700 dark:text-slate-300">Akun Aktif</span>
                        </label>
                    </div>
                </div>

                {{-- Full Width: Description --}}
                <div class="md:col-span-2">
                    <label for="description" class="form-label label-optional">Deskripsi / Catatan</label>
                    <textarea id="description" name="description" rows="3" 
                              class="form-textarea" 
                              placeholder="Deskripsi penggunaan akun ini...">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.chart-of-accounts.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-2">save</i> Simpan Akun
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function coaForm() {
        return {
            selectedType: '{{ old('account_type') }}',
            normalBalance: '{{ old('normal_balance', 'Debit') }}',

            updateNormalBalance() {
                // Logika Akuntansi Dasar
                const type = this.selectedType;
                if (['Aset', 'Beban', 'HPP'].includes(type)) {
                    this.normalBalance = 'Debit';
                } else if (['Liabilitas', 'Ekuitas', 'Pendapatan'].includes(type)) {
                    this.normalBalance = 'Kredit';
                }
            }
        }
    }
</script>
@endpush
@endsection