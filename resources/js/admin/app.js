// resources/js/admin/app.js

import '../bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import Library Global
import TomSelect from 'tom-select';
import AutoNumeric from 'autonumeric';

// [PERUBAHAN]: Import Logic Alert Baru (Bukan SweetAlert lagi)
import { showToast, showConfirmDialog } from '../components/alerts';

// Import Components Sidebar
import { initSidebarLogic } from '../components/sidebar';

// Setup Alpine
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Setup Library Global
window.TomSelect = TomSelect;
window.AutoNumeric = AutoNumeric;

// [PERUBAHAN]: Expose Fungsi Alert Baru ke Global Window
// Agar bisa dipanggil di Blade via @push('scripts')
window.showToast = showToast; 
window.confirmDialog = showConfirmDialog; // Kita namakan confirmDialog untuk menggantikan Swal

// =============================================================================
// CONFIG DEFAULT TOM SELECT (Tidak Berubah)
// =============================================================================
window.defaultTomSelectConfig = {
    sortField: { field: "text", direction: "asc" },
    plugins: ['clear_button'],
    dropdownParent: 'body',
    render: {
        no_results: function(data, escape) {
            return '<div class="no-results p-3 text-sm text-slate-500 text-center italic">Data tidak ditemukan</div>';
        }
    },
    onInitialize: function() {
        if(this.wrapper) this.wrapper.classList.add('ts-wrapper-custom');
    }
};

// =============================================================================
// CONFIG DEFAULT AUTONUMERIC (Tidak Berubah)
// =============================================================================
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
// MAIN INITIALIZATION
// =============================================================================
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

document.addEventListener('DOMContentLoaded', function () {
    initSidebarLogic();
    // [PERUBAHAN]: Hapus initToast() lama karena logic baru tidak perlu init

    // 1. Handle Flash Messages
    // Menggunakan logic baru showToast
    if (window.laravelFlash && window.laravelFlash.length > 0) {
        window.laravelFlash.forEach(flash => {
            showToast(flash.message, flash.type);
        });
        window.laravelFlash = [];
    }

    // 2. INIT GLOBAL AUTONUMERIC & HIDDEN INPUT SYNC (Tidak Berubah)
    const anInputs = document.querySelectorAll('.autonumeric');
    anInputs.forEach(el => {
        if (AutoNumeric.getAutoNumericElement(el)) {
            return; 
        }

        const anInstance = new AutoNumeric(el, window.defaultAutoNumericOptions);
        const originalName = el.getAttribute('name');
        
        if(originalName && !el.hasAttribute('data-an-synced')) {
            el.setAttribute('data-an-synced', 'true');
            el.setAttribute('name', originalName + '_visual');
            
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = originalName;
            hiddenInput.value = anInstance.getNumericString(); 
            
            el.parentNode.insertBefore(hiddenInput, el.nextSibling);

            el.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
            });
        }
    });

    // 3. INIT TOM SELECT (Tidak Berubah)
    document.querySelectorAll('.tom-select').forEach(el => {
        if (el.tomselect) return; 
        new TomSelect(el, window.defaultTomSelectConfig);
    });

    // 4. LOADING BUTTON ANIMATION (Tidak Berubah)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if(!form.checkValidity()) return;

            const btn = form.querySelector('button[type="submit"]');
            if(btn && !btn.classList.contains('no-loading') && !btn.classList.contains('btn-danger')) {
                btn.classList.add('is-loading');
            }
        });
    });
});