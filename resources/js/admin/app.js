// resources/js/admin/app.js

import '../bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import Library Global
import TomSelect from 'tom-select';
import AutoNumeric from 'autonumeric';

// Import Custom Components (Pastikan file ini ada di path yang sesuai)
import { showToast, showConfirmDialog } from '../components/alerts';
import { initSidebarLogic } from '../components/sidebar';

// Setup Alpine Plugin
Alpine.plugin(collapse);

// Expose Library ke Window agar bisa diakses inline script (Blade)
window.Alpine = Alpine;
window.TomSelect = TomSelect;
window.AutoNumeric = AutoNumeric;
window.showToast = showToast;
window.confirmDialog = showConfirmDialog;

// =============================================================================
// 1. KONFIGURASI DEFAULT
// =============================================================================

// Konfigurasi Tom Select
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
        // Fix untuk styling border tailwind
        if(this.wrapper) this.wrapper.classList.add('ts-wrapper-custom');
        
        // Trigger event change manual agar form dirty detection (unsaved changes) bekerja
        this.on('change', () => {
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }
};

// Konfigurasi AutoNumeric (Format Indonesia)
window.defaultAutoNumericOptions = {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalCharacterAlternative: '.',
    currencySymbol: '', 
    currencySymbolPlacement: 'p',
    roundingMethod: 'U',
    minimumValue: '0',
    unformatOnSubmit: true 
};

// =============================================================================
// 2. GLOBAL INIT COMPONENTS (CRITICAL FOR DYNAMIC ROWS)
// =============================================================================

/**
 * Event Listener Khusus untuk AlpineJS
 * Dipanggil dengan: $dispatch('reinit-components') saat menambah baris baru
 */
document.addEventListener('reinit-components', () => {
    // Delay sedikit agar DOM selesai dirender oleh Alpine sebelum di-init
    setTimeout(() => {
        window.initComponents();
    }, 150);
});

window.initComponents = function (scope = document) {

    // A. INIT AUTONUMERIC (Handling Visual vs Hidden Input)
    const anInputs = scope.querySelectorAll('.autonumeric');
    
    anInputs.forEach(el => {
        // Skip jika sudah di-init sebelumnya agar tidak double instance
        if (AutoNumeric.getAutoNumericElement(el)) return;

        // 1. Inisialisasi AutoNumeric pada elemen visual
        const anInstance = new AutoNumeric(el, window.defaultAutoNumericOptions);
        const originalName = el.getAttribute('name');

        // 2. Logic Sinkronisasi ke Hidden Input
        // Jika elemen punya 'name', kita ubah jadi '_visual' dan buat hidden input asli
        if(originalName && !el.hasAttribute('data-an-synced')) {
            el.setAttribute('data-an-synced', 'true');

            // Rename input visual (misal: "amount" jadi "amount_visual")
            // Agar yang terkirim ke server bukan "1.000.000" (string), tapi hidden input
            el.setAttribute('name', originalName + '_visual');

            // Buat hidden input untuk menampung nilai murni (integer/float)
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = originalName;
            
            // Set nilai awal (dari value yang ada di visual, tapi unformatted)
            hiddenInput.value = anInstance.getNumericString() || 0;

            // Masukkan hidden input tepat setelah input visual
            el.parentNode.insertBefore(hiddenInput, el.nextSibling);

            // 3. Event Listener: Saat visual diketik, update hidden input
            el.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
                // Trigger event change pada hidden input agar Alpine/Unsaved Warning tahu
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
    });

    // B. INIT TOM SELECT
    const tsInputs = scope.querySelectorAll('.tom-select, .tom-select-dynamic');
    tsInputs.forEach(el => {
        // Skip jika sudah di-init (cek property tomselect pada elemen)
        if (el.tomselect) return;

        // Ambil config bawaan
        let config = { ...window.defaultTomSelectConfig };
        
        // Inisialisasi
        new TomSelect(el, config);
    });

    // C. RE-INIT FLOWBITE (Untuk Tooltips, Dropdowns yang baru muncul)
    if(typeof initFlowbite === 'function') {
        initFlowbite();
    }
};

// =============================================================================
// 3. FITUR: UNSAVED CHANGES WARNING
// =============================================================================
function initUnsavedChangesWarning() {
    // GANTI 'let isDirty' MENJADI 'window.isFormDirty'
    window.isFormDirty = false; 

    // Deteksi perubahan pada form
    const forms = document.querySelectorAll('form:not([method="GET"]):not(.ignore-unsaved)');

    forms.forEach(form => {
        // Saat user mengetik/memilih, tandai form kotor
        form.addEventListener('change', () => { window.isFormDirty = true; });
        form.addEventListener('input', () => { window.isFormDirty = true; });

        // Jika submit standar, reset jadi bersih
        form.addEventListener('submit', () => {
            window.isFormDirty = false;
        });
    });

    // Listener saat user mencoba meninggalkan halaman
    window.addEventListener('beforeunload', (e) => {
        if (window.isFormDirty) {
            e.preventDefault();
            e.returnValue = 'Anda memiliki perubahan yang belum disimpan.';
            return 'Anda memiliki perubahan yang belum disimpan.';
        }
    });
}

// =============================================================================
// 4. MAIN INITIALIZATION
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
    
    // 1. Init Sidebar Logic (Toggle, Mobile Overlay)
    if(typeof initSidebarLogic === 'function') initSidebarLogic();

    // 2. Init Unsaved Changes Logic
    initUnsavedChangesWarning();

    // 3. Init Komponen UI (TomSelect, AutoNumeric) - PENTING
    window.initComponents();

    // 4. Handle Flash Messages dari Laravel Session (Jika ada variable global)
    if (window.laravelFlash && window.laravelFlash.length > 0) {
        window.laravelFlash.forEach(flash => {
            showToast(flash.message, flash.type);
        });
        // Clear agar tidak muncul lagi saat navigasi SPA (jika pakai)
        window.laravelFlash = [];
    }

    // 5. LOADING BUTTON ANIMATION
    // Otomatis menambahkan spinner pada tombol submit saat diklik
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Validasi HTML5 Native sebelum loading
            if(!form.checkValidity()) return;

            const btn = form.querySelector('button[type="submit"]');
            
            // Cek pengecualian (tombol delete merah biasanya butuh confirm dialog, bukan loading langsung)
            // Atau tombol dengan class 'no-loading'
            if(btn && !btn.classList.contains('no-loading') && !btn.classList.contains('btn-action-delete')) {
                btn.classList.add('is-loading');
                
                // Safety: Hapus loading setelah 15 detik jika server hang/timeout
                setTimeout(() => {
                    btn.classList.remove('is-loading');
                }, 15000);
            }
        });
    });
});