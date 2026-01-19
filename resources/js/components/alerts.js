// resources/js/components/alerts.js

/**
 * =============================================================================
 * 1. TOAST HANDLER (Flowbite Style - Native Tailwind)
 * Menampilkan notifikasi pojok kanan atas.
 * =============================================================================
 */
export function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = "fixed top-4 right-4 z-[70] flex flex-col gap-3";
        document.body.appendChild(container);
    }

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

    const toastEl = document.createElement('div');
    toastEl.className = `flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-xl shadow-lg dark:text-gray-400 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 transform transition-all duration-300 translate-x-full opacity-0`;
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

    container.appendChild(toastEl);

    requestAnimationFrame(() => {
        toastEl.classList.remove('translate-x-full', 'opacity-0');
    });

    const removeToast = () => {
        toastEl.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
        }, 350);
    };

    const closeBtn = toastEl.querySelector('button');
    if (closeBtn) closeBtn.addEventListener('click', removeToast);

    setTimeout(removeToast, 4000);
}

/**
 * =============================================================================
 * 2. MODAL CONFIRM HANDLER (Promise Based) - CLEAN VERSION
 * =============================================================================
 */
export function showConfirmDialog(config) {
    return new Promise((resolve) => {
        const title = config.title || 'Konfirmasi';
        const text = config.text || config.html || 'Apakah Anda yakin?';

        // Mapping Icon
        let iconName = 'warning_amber';
        let iconBgColor = 'bg-amber-100 text-amber-500 dark:bg-amber-900/30 dark:text-amber-400';
        
        const iconType = config.icon || 'warning';

        if (iconType === 'question') {
            iconName = 'help_outline';
            iconBgColor = 'bg-blue-100 text-blue-500 dark:bg-blue-900/30 dark:text-blue-400';
        }
        else if (iconType === 'error') {
            iconName = 'error_outline';
            iconBgColor = 'bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-400';
        }
        else if (iconType === 'success') {
            iconName = 'check_circle';
            iconBgColor = 'bg-emerald-100 text-emerald-500 dark:bg-emerald-900/30 dark:text-emerald-400';
        }
        else if (iconType === 'info') {
            iconName = 'info';
            iconBgColor = 'bg-cyan-100 text-cyan-500 dark:bg-cyan-900/30 dark:text-cyan-400';
        }

        const confirmBtnText = config.confirmText || config.confirmButtonText || 'Ya, Lanjutkan';
        const cancelBtnText = config.cancelText || config.cancelButtonText || 'Batal';

        // Mapping Warna Tombol
        let btnColorClass = "text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 shadow-indigo-500/20"; 
        const inputColor = config.confirmColor || config.confirmButtonColor || '';

        if (inputColor === 'danger' || inputColor.includes('red') || inputColor.includes('#d33')) {
            btnColorClass = "text-white bg-rose-600 hover:bg-rose-700 focus:ring-4 focus:ring-rose-300 shadow-rose-500/20";
        } else if (inputColor === 'success' || inputColor.includes('green')) {
            btnColorClass = "text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 shadow-emerald-500/20";
        } else if (inputColor === 'warning' || inputColor.includes('orange')) {
            btnColorClass = "text-white bg-amber-600 hover:bg-amber-700 focus:ring-4 focus:ring-amber-300 shadow-amber-500/20";
        }

        const overlay = document.createElement('div');
        overlay.className = "fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0 duration-200 p-4";

        overlay.innerHTML = `
            <div class="relative w-full max-w-md max-h-full transition-all transform scale-95 duration-200">
                <div class="relative bg-white rounded-2xl shadow-2xl dark:bg-slate-800 border border-slate-100 dark:border-slate-700 overflow-hidden">

                    <button type="button" class="absolute top-4 right-4 text-slate-400 bg-transparent hover:bg-slate-100 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-slate-700 dark:hover:text-white transition-colors" id="btn-close-modal">
                        <i class="material-icons text-xl">close</i>
                        <span class="sr-only">Close modal</span>
                    </button>

                    <div class="p-6 pt-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full ${iconBgColor} mb-6">
                            <i class="material-icons text-4xl">${iconName}</i>
                        </div>

                        <h3 class="mb-3 text-xl font-bold text-slate-900 dark:text-white tracking-tight">${title}</h3>
                        
                        <div class="mb-8 text-sm text-slate-500 dark:text-slate-400 leading-relaxed px-2">
                            ${text}
                        </div>

                        <div class="flex justify-center gap-3">
                            <button id="btn-confirm-action" type="button" class="${btnColorClass} font-medium rounded-xl text-sm inline-flex items-center px-6 py-3 text-center focus:outline-none shadow-lg transition-all transform hover:-translate-y-0.5">
                                ${confirmBtnText}
                            </button>

                            <button id="btn-cancel-action" type="button" class="text-slate-700 bg-white hover:bg-slate-50 focus:ring-4 focus:outline-none focus:ring-slate-200 rounded-xl border border-slate-200 text-sm font-medium px-6 py-3 hover:text-slate-900 focus:z-10 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 dark:hover:text-white dark:hover:bg-slate-700 transition-colors">
                                ${cancelBtnText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            const modalBox = overlay.querySelector('div > div');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');

            const cancelBtn = overlay.querySelector('#btn-cancel-action');
            if(cancelBtn) cancelBtn.focus();
        });

        const close = (isConfirmed) => {
            overlay.classList.add('opacity-0');
            const modalBox = overlay.querySelector('div > div');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');

            setTimeout(() => {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                resolve({
                    isConfirmed: isConfirmed,
                    isDenied: !isConfirmed,
                    isDismissed: !isConfirmed
                });
            }, 200);
        };

        overlay.querySelector('#btn-confirm-action').addEventListener('click', () => close(true));
        overlay.querySelector('#btn-cancel-action').addEventListener('click', () => close(false));
        overlay.querySelector('#btn-close-modal').addEventListener('click', () => close(false));

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close(false);
        });

        document.addEventListener('keydown', function escListener(e) {
            if (e.key === 'Escape') {
                close(false);
                document.removeEventListener('keydown', escListener);
            }
        });
    });
}