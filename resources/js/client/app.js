// resources/js/client/app.js

import '../bootstrap'; 
import 'flowbite';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import Library Global
import TomSelect from 'tom-select';
import AutoNumeric from 'autonumeric';

// [REUSE]: Import Logic Alert & Sidebar dari komponen Admin yang sudah ada
// Pastikan path relative-nya benar mengarah ke folder js/components
import { showToast, showConfirmDialog } from '../../js/components/alerts'; 
import { initSidebarLogic } from '../../js/components/sidebar';

// Setup Alpine
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Setup Library Global
window.TomSelect = TomSelect;
window.AutoNumeric = AutoNumeric;
window.showToast = showToast; 
window.confirmDialog = showConfirmDialog; 

// =============================================================================
// CONFIG DEFAULT TOM SELECT (SAMA DENGAN ADMIN)
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
        this.on('change', () => {
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
};

// =============================================================================
// CONFIG DEFAULT AUTONUMERIC
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
// FITUR: UNSAVED CHANGES WARNING (CLIENT PORTAL)
// =============================================================================
function initUnsavedChangesWarning() {
    let isDirty = false;
    // Kecualikan search form (method GET)
    const forms = document.querySelectorAll('form:not([method="GET"])');

    forms.forEach(form => {
        if (form.classList.contains('ignore-unsaved')) return;

        form.addEventListener('change', () => { isDirty = true; });
        form.addEventListener('input', () => { isDirty = true; });

        form.addEventListener('submit', () => {
            isDirty = false;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Perubahan belum disimpan.';
            return 'Perubahan belum disimpan.';
        }
    });
}

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
    // Init Sidebar Logic
    initSidebarLogic();

    // Init Unsaved Changes Warning
    initUnsavedChangesWarning();

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
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
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
                setTimeout(() => {
                    btn.classList.remove('is-loading');
                }, 10000);
            }
        });
    });
});