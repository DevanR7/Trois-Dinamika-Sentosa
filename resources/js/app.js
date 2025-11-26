import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import AutoNumeric from 'autonumeric';

// -----------------------------------------------------------------------------
// 1. ALPINE STORE: DARK MODE
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
window.Swal = Swal;
window.AutoNumeric = AutoNumeric;

Alpine.start();

// -----------------------------------------------------------------------------
// 2. KONFIGURASI GLOBAL AUTONUMERIC
// -----------------------------------------------------------------------------
const autoNumericOptions = {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalCharacterAlternative: '.',
    currencySymbol: 'Rp ',
    currencySymbolPlacement: 'p',
    roundingMethod: 'U',
    minimumValue: '0',
    aPad: false
};

// -----------------------------------------------------------------------------
// 3. GLOBAL FUNCTIONS: ACCORDION, TOAST
// -----------------------------------------------------------------------------

// Accordion
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

// Toast
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

// -----------------------------------------------------------------------------
// 4. DOM LOADED EVENT LISTENER
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // A. AutoNumeric init untuk semua input Rupiah
    const currencyInputs = document.querySelectorAll('.input-currency');
    currencyInputs.forEach(el => {
        new AutoNumeric(el, autoNumericOptions);
    });

    // B. Select2 auto-init
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-basic').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder') || '-- Pilih --',
                allowClear: Boolean($(this).data('allow-clear')),
                dropdownCssClass: 'select2-dropdown-clean'
            });
        });
    }

    // C. Universal Delete/Confirm Handler
    document.body.addEventListener('submit', function (e) {
        const form = e.target.closest('.form-confirm, .delete-form');

        if (form) {
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const isDeleteDefault = form.classList.contains('delete-form');
            const name = button?.dataset.name || 'item ini';

            let config = {
                title: 'Hapus Data?',
                text: `Anda yakin ingin menghapus <b>${name}</b>?`,
                btnText: 'Ya, Hapus!',
                btnColor: '#ef4444',
                icon: 'warning'
            };

            if (!isDeleteDefault) {
                config.title = button.dataset.title || config.title;
                config.text = button.dataset.text || config.text;
                config.btnText = button.dataset.btnText || config.btnText;
                config.btnColor = button.dataset.btnColor || '#6366f1';
                config.icon = button.dataset.icon || 'question';
            }

            Swal.fire({
                title: config.title,
                html: config.text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: config.btnColor,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: config.btnText,
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'bg-white rounded-xl border border-slate-100 shadow-xl p-6',
                    title: 'text-xl font-bold text-slate-800',
                    htmlContainer: 'text-sm text-slate-600 mt-2',
                    confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-md',
                    cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    });

    // -------------------------------------------------------------------------
    // 5. SIDEBAR LOGIC (UTUH DARI KODE LAMA)
    // -------------------------------------------------------------------------
    const sidebar = document.getElementById("mainSidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const sidebarCloseMobile = document.getElementById("sidebar-close");
    const sidebarOverlay = document.getElementById("sidebar-overlay");
    const lockIcon = document.getElementById("lock-icon");
    const scrollContainer = document.getElementById('sidebarScrollContainer');

    let isSidebarLocked = localStorage.getItem('isSidebarLocked') === 'true';
    const isDesktop = () => window.innerWidth >= 1024;

    function initSidebar() {
        const nuclear = document.getElementById('nuclear-sidebar-style');
        if (nuclear) nuclear.remove();

        if (isDesktop()) {
            if (isSidebarLocked) lockSidebar();
            else unlockSidebar();
        } else {
            sidebar?.classList.add('close', 'mobile-closed');
            sidebar?.classList.remove('locked', 'hover-expand', 'mobile-open');
        }

        setTimeout(() => sidebar?.classList.remove('preload'), 300);
        restoreScrollPosition();
        updateLockIcon();
    }

    function lockSidebar() {
        isSidebarLocked = true;
        localStorage.setItem('isSidebarLocked', 'true');
        sidebar?.classList.add('locked');
        sidebar?.classList.remove('close', 'hover-expand');
        updateLockIcon();
    }

    function unlockSidebar() {
        isSidebarLocked = false;
        localStorage.setItem('isSidebarLocked', 'false');
        sidebar?.classList.remove('locked');
        sidebar?.classList.add('close');
        updateLockIcon();
    }

    function updateLockIcon() {
        if (!lockIcon) return;
        if (isSidebarLocked) {
            lockIcon.innerText = 'radio_button_checked';
            lockIcon.classList.add('text-indigo-400');
        } else {
            lockIcon.innerText = 'radio_button_unchecked';
            lockIcon.classList.remove('text-indigo-400');
        }
    }

    function toggleMobile() {
        const isOpen = sidebar.classList.contains('mobile-open');
        if (isOpen) {
            sidebar.classList.remove('mobile-open');
            sidebar.classList.add('mobile-closed');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.remove('mobile-closed', 'close');
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    sidebar?.addEventListener('mouseenter', () => {
        if (isDesktop() && !isSidebarLocked) {
            sidebar.classList.add('hover-expand');
            if (lockIcon) lockIcon.style.display = 'block';
        }
    });

    sidebar?.addEventListener('mouseleave', () => {
        if (isDesktop() && !isSidebarLocked) {
            sidebar.classList.remove('hover-expand');
        }
    });

    if (lockIcon) {
        lockIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isDesktop()) {
                if (isSidebarLocked) unlockSidebar();
                else lockSidebar();
            }
        });
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleMobile);
    if (sidebarCloseMobile) sidebarCloseMobile.addEventListener('click', toggleMobile);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMobile);

    window.addEventListener('resize', () => {
        if (!isDesktop()) {
            sidebar?.classList.remove('locked', 'hover-expand', 'mobile-open');
            sidebar?.classList.add('close', 'mobile-closed');
            sidebarOverlay?.classList.add('hidden');
        } else {
            sidebar?.classList.remove('mobile-open');
            sidebarOverlay?.classList.add('hidden');
            if (isSidebarLocked) lockSidebar();
            else unlockSidebar();
        }
    });

    // Submenu
    document.querySelectorAll('.has-submenu > .link').forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#' || !href || href.includes('javascript')) {
                e.preventDefault();

                const parentLi = this.parentElement;
                const wasOpen = parentLi.classList.contains('open');

                document.querySelectorAll('.has-submenu.open').forEach(item => {
                    if (item !== parentLi) item.classList.remove('open');
                });

                if (wasOpen) {
                    parentLi.classList.remove('open');
                } else {
                    parentLi.classList.add('open');
                    if (sidebar.classList.contains('close') && !isSidebarLocked) {
                        sidebar.classList.add('hover-expand');
                    }
                }
            }
        });
    });

    if (scrollContainer) {
        scrollContainer.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScrollPos', scrollContainer.scrollTop);
        });
    }

    function restoreScrollPosition() {
        if (scrollContainer) {
            const savedScrollPos = localStorage.getItem('sidebarScrollPos');
            if (savedScrollPos) scrollContainer.scrollTop = parseInt(savedScrollPos);
        }
    }

    initSidebar();
});
