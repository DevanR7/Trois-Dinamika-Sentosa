import '../bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
import TomSelect from 'tom-select'; 

// Import Modul Custom
import { initSidebarLogic } from '../components/sidebar';
import { initToast, initConfirmHandlers } from '../components/alerts';
import { initFormatters } from '../components/formatters';

// -----------------------------------------------------------------------------
// 1. ALPINE JS
// -----------------------------------------------------------------------------
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

window.Alpine = Alpine;
Alpine.start();

// -----------------------------------------------------------------------------
// 2. GLOBAL HELPER
// -----------------------------------------------------------------------------
window.toggleAccordion = function (id) {
    const wrapper = document.getElementById('wrapper-' + id);
    const icon = document.getElementById('icon-' + id);
    if (!wrapper) return;

    if (wrapper.classList.contains('grid-rows-[0fr]')) {
        wrapper.classList.remove('grid-rows-[0fr]');
        wrapper.classList.add('grid-rows-[1fr]');
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        wrapper.classList.remove('grid-rows-[1fr]');
        wrapper.classList.add('grid-rows-[0fr]');
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
};

// -----------------------------------------------------------------------------
// 3. INISIALISASI
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // Init Modul Utama
    initSidebarLogic();      // Sidebar Client
    initToast();             // Notifikasi
    initConfirmHandlers();   // Konfirmasi Submit
    initFormatters();        // Format Rupiah (berguna jika Client ada form topup/bayar)

    // Init Tom Select untuk Client (Opsional)
    const tomSelects = document.querySelectorAll('.tom-select');
    if (tomSelects.length > 0) {
        tomSelects.forEach((el) => {
            new TomSelect(el, {
                create: false,
                sortField: { field: "text", direction: "asc" },
                placeholder: el.dataset.placeholder || 'Pilih...',
                plugins: ['remove_button']
            });
        });
    }
});