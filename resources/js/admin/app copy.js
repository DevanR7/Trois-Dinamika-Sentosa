// resources/js/admin/app.js

import '../bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import Library Global
import TomSelect from 'tom-select';
import AutoNumeric from 'autonumeric';

// Import Custom Components
import { showToast, showConfirmDialog } from '../components/alerts';
import { initSidebarLogic } from '../components/sidebar';

// Setup Alpine
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Setup Library Global ke Window agar bisa diakses inline script jika perlu
window.TomSelect = TomSelect;
window.AutoNumeric = AutoNumeric;
window.showToast = showToast;
window.confirmDialog = showConfirmDialog;

// =============================================================================
// 1. CONFIG DEFAULT
// =============================================================================

// Konfigurasi Default Tom Select
window.defaultTomSelectConfig = {
    sortField: { field: "text", direction: "asc" },
    plugins: ['clear_button'],
    // PENTING: dropdownParent 'body' agar tidak terpotong overflow tabel/modal
    dropdownParent: 'body', 
    render: {
        no_results: function(data, escape) {
            return '<div class="no-results p-3 text-sm text-slate-500 text-center italic">Data tidak ditemukan</div>';
        }
    },
    onInitialize: function() {
        // Tambahkan class custom wrapper jika diperlukan styling tambahan
        if(this.wrapper) this.wrapper.classList.add('ts-wrapper-custom');
        
        // Integrasi dengan Unsaved Changes Warning
        // Tom Select mematikan event change asli, kita trigger manual agar form dirty terdeteksi
        this.on('change', () => {
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }
};

// Konfigurasi Default AutoNumeric
window.defaultAutoNumericOptions = {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalCharacterAlternative: '.',
    currencySymbol: '', 
    currencySymbolPlacement: 'p',
    roundingMethod: 'U',
    minimumValue: '0',
    unformatOnSubmit: true // Opsional, kita handle manual via hidden input agar lebih aman
};

// =============================================================================
// 2. GLOBAL INIT COMPONENTS (PENTING UNTUK DYNAMIC ROWS)
// Fungsi ini bisa dipanggil ulang saat Anda menambah baris baru via JS
// =============================================================================
window.initComponents = function (scope = document) {
    
    // A. INIT AUTONUMERIC (Handling Visual vs Hidden Input)
    const anInputs = scope.querySelectorAll('.autonumeric');
    anInputs.forEach(el => {
        // Skip jika sudah di-init sebelumnya
        if (AutoNumeric.getAutoNumericElement(el)) return; 

        // Inisialisasi AutoNumeric pada elemen visual
        const anInstance = new AutoNumeric(el, window.defaultAutoNumericOptions);
        const originalName = el.getAttribute('name');
        
        // Logic sinkronisasi ke Hidden Input
        if(originalName && !el.hasAttribute('data-an-synced')) {
            el.setAttribute('data-an-synced', 'true');
            
            // Rename input visual (misal: "amount" jadi "amount_visual")
            // Agar yang terkirim ke server bukan "1.000.000" (string), tapi hidden input di bawahnya
            el.setAttribute('name', originalName + '_visual');
            
            // Buat hidden input untuk menampung nilai murni (integer/float)
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = originalName;
            hiddenInput.value = anInstance.getNumericString(); // Set nilai awal
            
            // Masukkan hidden input tepat setelah input visual
            el.parentNode.insertBefore(hiddenInput, el.nextSibling);

            // Event Listener: Saat visual diketik, update hidden input
            el.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
                
                // Trigger event change pada hidden input agar Unsaved Warning mendeteksi perubahan
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    // B. INIT TOM SELECT
    const tsInputs = scope.querySelectorAll('.tom-select');
    tsInputs.forEach(el => {
        // Skip jika sudah di-init
        if (el.tomselect) return; 
        
        // Ambil config bawaan, bisa di-override via attribute data-config jika perlu
        let config = { ...window.defaultTomSelectConfig };
        
        new TomSelect(el, config);
    });

    // C. RE-INIT FLOWBITE (Untuk Tooltips, Dropdowns yang baru muncul)
    if(window.initFlowbite) window.initFlowbite();
};

// =============================================================================
// 3. FITUR: UNSAVED CHANGES WARNING
// Mencegah refresh/navigasi jika form sudah diisi tapi belum disubmit
// =============================================================================
function initUnsavedChangesWarning() {
    let isDirty = false;

    // 1. Deteksi perubahan pada input form
    // Kecualikan form dengan method GET (biasanya search filter)
    const forms = document.querySelectorAll('form:not([method="GET"])');

    forms.forEach(form => {
        // Jika form punya class 'ignore-unsaved', lewati
        if (form.classList.contains('ignore-unsaved')) return;

        // Deteksi perubahan pada input, select, textarea
        form.addEventListener('change', () => { isDirty = true; });
        form.addEventListener('input', () => { isDirty = true; });

        // 2. Jika tombol submit ditekan, reset isDirty agar tidak muncul peringatan
        form.addEventListener('submit', () => {
            isDirty = false;
        });
    });

    // 3. Listener saat user mencoba meninggalkan halaman (Refresh/Back/Close Tab)
    window.addEventListener('beforeunload', (e) => {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Anda memiliki perubahan yang belum disimpan.';
            return 'Anda memiliki perubahan yang belum disimpan.';
        }
    });
}

// =============================================================================
// 4. MAIN INITIALIZATION (Alpine & DOM)
// =============================================================================

// Init Alpine Store untuk Dark Mode
document.addEventListener('alpine:init', () => {
    Alpine.store('darkMode', {
        on: localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),

        toggle() {
            this.on = !this.on;
            if (this.on) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }
    });
});

Alpine.start();

// Jalankan Script saat DOM Ready
document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Logic
    initSidebarLogic();
    
    // 2. Unsaved Changes Logic
    initUnsavedChangesWarning();

    // 3. Init Komponen (AutoNumeric, TomSelect) - PENTING
    window.initComponents();

    // 4. Handle Flash Messages dari Laravel Session
    if (window.laravelFlash && window.laravelFlash.length > 0) {
        window.laravelFlash.forEach(flash => {
            showToast(flash.message, flash.type);
        });
        window.laravelFlash = [];
    }

    // 5. LOADING BUTTON ANIMATION
    // Otomatis menambahkan spinner pada tombol submit saat diklik
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Validasi HTML5 Native sebelum loading
            if(!form.checkValidity()) {
                return; // Biarkan browser menampilkan error bubble
            }

            // Cari tombol submit di dalam form
            const btn = form.querySelector('button[type="submit"]');
            
            // Cek pengecualian (misal tombol delete warna merah, atau class no-loading)
            // Biasanya tombol delete pakai confirm dialog terpisah, jadi aman dikecualikan
            if(btn && !btn.classList.contains('no-loading') && !btn.classList.contains('btn-danger')) {
                btn.classList.add('is-loading');
                
                // Safety: Hapus loading setelah 15 detik jika server hang/timeout
                // Agar user bisa mencoba lagi
                setTimeout(() => {
                    btn.classList.remove('is-loading');
                }, 15000);
            }
        });
    });
});