// resources/js/components/alerts.js

/**
 * =============================================================================
 * 1. TOAST HANDLER (Flowbite Style - Native Tailwind)
 * Menampilkan notifikasi pojok kanan atas.
 * =============================================================================
 */
export function showToast(message, type = 'success') {
    // 1. Cek apakah container toast sudah ada, jika belum buat baru
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        // Styling container: Fixed di kanan atas, z-index tinggi
        container.className = "fixed top-4 right-4 z-[70] flex flex-col gap-3";
        document.body.appendChild(container);
    }

    // 2. Tentukan Icon & Warna (Theme Logic)
    let iconName = 'check_circle';
    let styles = '';

    switch (type) {
        case 'error':
            iconName = 'error_outline';
            styles = 'text-red-500 bg-red-100 dark:bg-red-800 dark:text-red-200';
            break;
        case 'warning':
            iconName = 'warning_amber';
            styles = 'text-orange-500 bg-orange-100 dark:bg-orange-700 dark:text-orange-200';
            break;
        case 'info':
            iconName = 'info';
            styles = 'text-blue-500 bg-blue-100 dark:bg-blue-800 dark:text-blue-200';
            break;
        default: // success
            iconName = 'check';
            styles = 'text-emerald-500 bg-emerald-100 dark:bg-emerald-800 dark:text-emerald-200';
            break;
    }

    // 3. Buat Element HTML Toast
    const toastEl = document.createElement('div');
    toastEl.className = `flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow-lg dark:text-gray-400 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 transform transition-all duration-300 translate-x-full opacity-0`;
    toastEl.setAttribute('role', 'alert');

    toastEl.innerHTML = `
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg ${styles}">
            <i class="material-icons text-lg">${iconName}</i>
        </div>
        <div class="ms-3 text-sm font-medium break-words leading-tight">${message}</div>
        <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors" aria-label="Close">
            <span class="sr-only">Close</span>
            <i class="material-icons text-sm">close</i>
        </button>
    `;

    // 4. Append ke Container
    container.appendChild(toastEl);

    // 5. Animasi Masuk (Slide In)
    requestAnimationFrame(() => {
        toastEl.classList.remove('translate-x-full', 'opacity-0');
    });

    // Fungsi Hapus Toast
    const removeToast = () => {
        toastEl.classList.add('translate-x-full', 'opacity-0'); // Animasi keluar
        setTimeout(() => {
            if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
        }, 350); // Tunggu durasi transisi CSS
    };

    // Event Listener Tombol Close
    const closeBtn = toastEl.querySelector('button');
    if (closeBtn) closeBtn.addEventListener('click', removeToast);

    // Auto Hapus setelah 4 detik
    setTimeout(removeToast, 4000);
}

/**
 * =============================================================================
 * 2. MODAL CONFIRM HANDLER (Promise Based)
 * Menggantikan: Swal.fire({...}).then((res) => { ... })
 * Memiliki logika FALLBACK agar kompatibel dengan kode SweetAlert lama.
 * =============================================================================
 */
export function showConfirmDialog(config) {
    return new Promise((resolve) => {
        // --- [LOGIKA FALLBACK / COMPATIBILITY] ---
        
        // 1. Ambil Title & Text
        const title = config.title || 'Konfirmasi';
        const text = config.text || config.html || 'Apakah Anda yakin?'; // SweetAlert kadang pakai 'html'
        
        // 2. Mapping Icon (SweetAlert String -> Material Icons)
        let iconName = 'warning_amber'; // Default
        const iconType = config.icon || 'warning';
        
        if (iconType === 'question') iconName = 'help_outline';
        else if (iconType === 'error') iconName = 'error_outline';
        else if (iconType === 'success') iconName = 'check_circle';
        else if (iconType === 'info') iconName = 'info';

        // 3. Mapping Text Tombol (Support 'confirmButtonText' milik SweetAlert)
        const confirmBtnText = config.confirmText || config.confirmButtonText || 'Ya, Lanjutkan';
        const cancelBtnText = config.cancelText || config.cancelButtonText || 'Batal';

        // 4. Mapping Warna Tombol (Hex Code SweetAlert -> Tailwind Class)
        // Kita mendeteksi input hex warna merah/hijau dari kode lama Anda
        let btnColorClass = "text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300"; // Default (Primary/Blue)
        
        // Cek inputan warna dari user (bisa berupa 'danger', 'success', atau HEX code)
        const inputColor = config.confirmColor || config.confirmButtonColor || '';

        // Deteksi Warna Merah (Danger) - Termasuk Hex #ef4444, #d33, dll
        if (inputColor === 'danger' || inputColor.includes('#ef4444') || inputColor.includes('#d33') || inputColor.includes('red')) {
            btnColorClass = "text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300";
        }
        // Deteksi Warna Hijau (Success) - Termasuk Hex #10b981, dll
        else if (inputColor === 'success' || inputColor.includes('#10b981') || inputColor.includes('green')) {
            btnColorClass = "text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300";
        }
        // Deteksi Warna Kuning (Warning)
        else if (inputColor === 'warning' || inputColor.includes('#f59e0b') || inputColor.includes('orange')) {
            btnColorClass = "text-white bg-amber-600 hover:bg-amber-700 focus:ring-4 focus:ring-amber-300";
        }

        // --- [END LOGIKA FALLBACK] ---


        // 5. Buat Overlay & Modal
        const overlay = document.createElement('div');
        // z-[60] agar di atas sidebar/navbar
        overlay.className = "fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0 duration-200";
        
        // HTML Structure (Flowbite Modal)
        overlay.innerHTML = `
            <div class="relative p-4 w-full max-w-md max-h-full transition-all transform scale-95 duration-200">
                <div class="relative bg-white rounded-xl shadow-2xl dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                    
                    <button type="button" class="absolute top-3 end-2.5 text-slate-400 bg-transparent hover:bg-slate-100 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-slate-700 dark:hover:text-white" id="btn-close-modal">
                        <i class="material-icons">close</i>
                        <span class="sr-only">Close modal</span>
                    </button>

                    <div class="p-6 text-center">
                        <i class="material-icons text-5xl mb-4 text-slate-500 dark:text-slate-400">${iconName}</i>
                        
                        <h3 class="mb-2 text-lg font-bold text-slate-800 dark:text-white">${title}</h3>
                        <p class="mb-6 text-sm font-normal text-slate-500 dark:text-slate-400 leading-relaxed">${text}</p>
                        
                        <div class="flex justify-center gap-3">
                            <button id="btn-confirm-action" type="button" class="${btnColorClass} font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center focus:outline-none shadow-sm transition-colors">
                                ${confirmBtnText}
                            </button>
                            
                            <button id="btn-cancel-action" type="button" class="text-slate-700 bg-white hover:bg-slate-50 focus:ring-4 focus:outline-none focus:ring-slate-200 rounded-lg border border-slate-300 text-sm font-medium px-5 py-2.5 hover:text-slate-900 focus:z-10 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600 dark:hover:text-white dark:hover:bg-slate-600 dark:focus:ring-slate-600 transition-colors">
                                ${cancelBtnText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // 6. Animasi Masuk
        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            const modalBox = overlay.querySelector('div > div');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
            
            // Fokus otomatis ke tombol cancel (untuk keamanan, agar tidak tidak sengaja enter)
            // Atau fokus ke confirm, tergantung preferensi. Disini saya fokus ke cancel.
            const cancelBtn = overlay.querySelector('#btn-cancel-action');
            if(cancelBtn) cancelBtn.focus();
        });

        // 7. Handler Tutup Modal
        const close = (isConfirmed) => {
            overlay.classList.add('opacity-0');
            const modalBox = overlay.querySelector('div > div');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');

            setTimeout(() => {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                // Return format objek yang mirip dengan SweetAlert result
                resolve({ 
                    isConfirmed: isConfirmed, 
                    isDenied: !isConfirmed, 
                    isDismissed: !isConfirmed 
                });
            }, 200); // Waktu animasi keluar
        };

        // 8. Event Listeners
        overlay.querySelector('#btn-confirm-action').addEventListener('click', () => close(true));
        overlay.querySelector('#btn-cancel-action').addEventListener('click', () => close(false));
        overlay.querySelector('#btn-close-modal').addEventListener('click', () => close(false));
        
        // Klik Backdrop untuk close
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close(false);
        });
        
        // Escape Key untuk close
        document.addEventListener('keydown', function escListener(e) {
            if (e.key === 'Escape') {
                close(false);
                document.removeEventListener('keydown', escListener);
            }
        });
    });
}