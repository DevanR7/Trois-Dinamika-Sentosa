import Swal from 'sweetalert2';

// 1. CONFIG TOAST (Notifikasi Pojok Atas)
export function initToast() {
    window.showToast = function (message, icon = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            iconColor: 'white',
            customClass: {
                popup: 'colored-toast' 
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: icon, title: message });
    };
}

// 2. CONFIG CONFIRMATION (Hapus / Submit / Approve)
export function initConfirmHandlers() {
    document.body.addEventListener('submit', function (e) {
        
        // Ambil elemen form yang sedang disubmit
        const form = e.target;
        
        // Cek apakah form ini memiliki class pemicu alert
        const isDelete = form.classList.contains('delete-form');
        const isGeneralConfirm = form.classList.contains('form-confirm');

        // Jika tidak ada kedua class tersebut, biarkan submit normal (return)
        if (!isDelete && !isGeneralConfirm) return;

        // Cegah submit browser bawaan
        e.preventDefault();

        // Ambil tombol yang diklik (untuk membaca data-attribute custom jika ada)
        const button = e.submitter; 
        
        // --- LOGIC PENENTUAN TIPE ALERT ---

        // A. DEFAULT CONFIG (Kosong dulu)
        let config = {};

        // B. SKENARIO 1: DELETE (Bahaya/Merah)
        if (isDelete) {
            // Ambil nama item dari data-name="Produk A" (jika ada)
            const itemName = button?.dataset.name || 'data ini'; 
            
            config = {
                title: 'Hapus Data?',
                html: `Apakah Anda yakin ingin menghapus <b>${itemName}</b>?<br><small class="text-slate-500">Tindakan ini tidak dapat dibatalkan.</small>`,
                icon: 'warning',
                confirmButtonText: 'Ya, Hapus!',
                confirmButtonColor: '#ef4444', // Merah (Danger)
                cancelButtonColor: '#94a3b8'   // Abu-abu
            };
        } 
        
        // C. SKENARIO 2: GENERAL CONFIRM (Aman/Biru)
        // Contoh: Approve pesanan, Submit laporan, dll
        else if (isGeneralConfirm) {
            config = {
                title: 'Konfirmasi Tindakan',
                html: 'Apakah Anda yakin ingin melanjutkan proses ini?',
                icon: 'question',
                confirmButtonText: 'Ya, Lanjutkan',
                confirmButtonColor: '#4f46e5', // Indigo (Primary)
                cancelButtonColor: '#64748b'   // Slate
            };
        }

        // D. OVERRIDE: Cek apakah tombol punya request khusus?
        // Ini berguna jika Anda ingin mengubah teks default tanpa ubah JS
        if (button) {
            if (button.dataset.title) config.title = button.dataset.title;
            if (button.dataset.text) config.html = button.dataset.text;
            if (button.dataset.icon) config.icon = button.dataset.icon;
            if (button.dataset.confirmColor) config.confirmButtonColor = button.dataset.confirmColor;
            if (button.dataset.confirmText) config.confirmButtonText = button.dataset.confirmText;
        }

        // --- EKSEKUSI SWEETALERT ---
        Swal.fire({
            title: config.title,
            html: config.html,
            icon: config.icon,
            showCancelButton: true,
            cancelButtonText: 'Batal',
            confirmButtonText: config.confirmButtonText,
            confirmButtonColor: config.confirmButtonColor,
            cancelButtonColor: config.cancelButtonColor,
            reverseButtons: true, // Tombol konfirmasi di kanan
            focusCancel: true,    // Fokus default ke tombol Batal (untuk safety)
            customClass: {
                popup: 'rounded-xl border border-slate-100 shadow-2xl', // Style tambahan
                title: 'text-xl font-bold text-slate-800 dark:text-slate-100',
                htmlContainer: 'text-sm text-slate-600 dark:text-slate-300'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika user klik Ya, submit form secara manual
                form.submit();
            }
        });
    });
}