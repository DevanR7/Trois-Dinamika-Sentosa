import '../bootstrap'; // Axios & Laravel Echo (jika pakai)
import 'flowbite';     // Framework UI
import Alpine from 'alpinejs';
import TomSelect from 'tom-select'; 

// Import Modul Custom
import { initSidebarLogic } from '../components/sidebar';
import { initToast, initConfirmHandlers } from '../components/alerts';
import { initFormatters } from '../components/formatters';

// -----------------------------------------------------------------------------
// 1. ALPINE JS & DARK MODE
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
// 2. GLOBAL HELPER (Accordion)
// -----------------------------------------------------------------------------
// Masih ditempel ke window agar bisa dipanggil via onclick di HTML
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
// 3. INISIALISASI SAAT DOM SIAP
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // A. Init Komponen Dasar
    initSidebarLogic();      // Sidebar interaktif
    initToast();             // Setup SweetAlert Toast
    initConfirmHandlers();   // Setup SweetAlert Delete Confirmation
    initFormatters();        // Setup AutoNumeric (Rupiah)

    // B. Init Tom Select (Pengganti Select2)
    // Cari elemen dengan class .tom-select
    document.querySelectorAll('.tom-select').forEach((el) => {
        new TomSelect(el, {
            create: false, // User tidak boleh buat opsi baru (set true jika boleh tags)
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: el.dataset.placeholder || 'Pilih Opsi...',
            plugins: {
                'remove_button': {
                    title: 'Hapus pilihan',
                },
                'clear_button': {
                    'title': 'Bersihkan semua',
                }
            }
        });
    });

    // Flowbite otomatis jalan karena import 'flowbite', 
    // tapi jika ada elemen dinamis (ajax), panggil initFlowbite();
});