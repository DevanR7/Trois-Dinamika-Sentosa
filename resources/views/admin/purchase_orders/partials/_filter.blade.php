<form action="{{ route('admin.purchase-orders.index') }}" method="GET">
    {{-- Menggunakan Grid System Tailwind --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        
        {{-- 1. PENCARIAN (Lebar: 3 kolom) --}}
        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Pencarian</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                {{-- name="search" disamakan dengan kodemu --}}
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                    placeholder="No. PO / Supplier / Faktur...">
            </div>
        </div>

        {{-- 2. TGL PESAN (Lebar: 2 kolom) --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Tgl. Pesan</label>
            {{-- name="order_date" disamakan dengan kodemu --}}
            <input type="date" name="order_date" value="{{ request('order_date') }}" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
        </div>

        {{-- 3. JATUH TEMPO (Lebar: 2 kolom) --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Jatuh Tempo</label>
            {{-- name="due_date" disamakan dengan kodemu --}}
            <input type="date" name="due_date" value="{{ request('due_date') }}" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
        </div>

        {{-- 4. STATUS BAYAR (Lebar: 2 kolom) --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Status Bayar</label>
            {{-- name="payment_status" disamakan dengan kodemu --}}
            <select name="payment_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                <option value="">-- Semua Status --</option>
                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>Cicil</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>
        </div>

        {{-- 5. TOMBOL AKSI (Lebar: 3 kolom) --}}
        <div class="md:col-span-3 flex gap-2">
            {{-- Tombol Filter (Style Gelap/Dark) --}}
            <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center">
                <i class="bi bi-funnel-fill mr-1"></i> Filter
            </button>
            
            {{-- Tombol Reset (Style Outline) --}}
            <a href="{{ route('admin.purchase-orders.index') }}" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition flex items-center justify-center" title="Reset Filter">
                <i class="bi bi-arrow-clockwise text-lg"></i>
            </a>
        </div>
    </div>
</form>