// resources/js/client/app.js

import '../bootstrap'; // Path relative ke folder bootstrap default laravel
import 'flowbite';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import Library Global
import TomSelect from 'tom-select';
import AutoNumeric from 'autonumeric';

// [REUSE]: Import Logic Alert & Sidebar dari komponen Admin yang sudah ada
// Pastikan path relative-nya benar mengarah ke folder js components
import { showToast, showConfirmDialog } from '../../js/components/alerts'; 
import { initSidebarLogic } from '../../js/components/sidebar';

// Setup Alpine
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Setup Library Global
window.TomSelect = TomSelect;
window.AutoNumeric = AutoNumeric;

// Expose Fungsi Alert ke Global Window (agar bisa dipanggil di Blade Client)
window.showToast = showToast; 
window.confirmDialog = showConfirmDialog; 

// =============================================================================
// CONFIG DEFAULT TOM SELECT (SAMA DENGAN ADMIN)
// =============================================================================
window.defaultTomSelectConfig = {
    sortField: { field: "text", direction: "asc" },
    plugins: ['clear_button'],
    dropdownParent: 'body', // WAJIB: Agar tidak terpotong di tabel/modal
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
// CONFIG DEFAULT AUTONUMERIC (SAMA DENGAN ADMIN)
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
// MAIN INITIALIZATION CLIENT
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
    // Init Sidebar Logic (Toggle Mobile, Collapse, dll)
    initSidebarLogic();

    // 1. Handle Flash Messages
    if (window.laravelFlash && window.laravelFlash.length > 0) {
        window.laravelFlash.forEach(flash => {
            showToast(flash.message, flash.type);
        });
        window.laravelFlash = [];
    }

    // 2. INIT GLOBAL AUTONUMERIC
    const anInputs = document.querySelectorAll('.autonumeric');
    anInputs.forEach(el => {
        if (AutoNumeric.getAutoNumericElement(el)) return; 

        const anInstance = new AutoNumeric(el, window.defaultAutoNumericOptions);
        const originalName = el.getAttribute('name');
        
        // Logic input hidden agar data terkirim clean (integer)
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

    // 3. INIT TOM SELECT
    document.querySelectorAll('.tom-select').forEach(el => {
        if (el.tomselect) return; 
        new TomSelect(el, window.defaultTomSelectConfig);
    });

    // 4. LOADING BUTTON ANIMATION
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