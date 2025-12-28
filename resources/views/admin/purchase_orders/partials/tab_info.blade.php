<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Detail Supplier</h4>
        <div class="space-y-4">
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">Nama</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->supplier->supplier_name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">PIC</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->supplier->person_in_charge ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">Telepon</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->supplier->phone_number ?? '-' }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-slate-500 text-sm">Alamat</span>
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-700 p-3 rounded">
                    {{ $purchaseOrder->supplier->address ?? '-' }}
                </p>
            </div>
        </div>
    </div>
    
    <div>
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Informasi Dokumen</h4>
        <div class="space-y-4">
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">No. Invoice Supplier</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->supplier_invoice_number ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">Dibuat Oleh</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->user_id_admin ? 'Admin' : 'System' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-slate-500 text-sm">Diminta Oleh</span>
                <span class="font-medium text-slate-800 dark:text-white">{{ $purchaseOrder->requester->full_name ?? '-' }}</span>
            </div>
            <div class="mt-4">
                <span class="text-slate-500 text-sm block mb-1">Catatan Internal</span>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 p-3 rounded text-sm text-slate-700 dark:text-slate-300 italic">
                    {{ $purchaseOrder->notes ?? 'Tidak ada catatan.' }}
                </div>
            </div>
        </div>
    </div>
</div>