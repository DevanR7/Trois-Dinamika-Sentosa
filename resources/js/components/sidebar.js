export function initSidebarLogic() {
    const sidebar = document.getElementById("mainSidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const sidebarCloseMobile = document.getElementById("sidebar-close");
    const sidebarOverlay = document.getElementById("sidebar-overlay");
    const lockIcon = document.getElementById("lock-icon");
    const scrollContainer = document.getElementById('sidebarScrollContainer');

    // Jika elemen tidak ada, stop (agar tidak error di halaman login/public)
    if (!sidebar) return;

    let isSidebarLocked = localStorage.getItem('isSidebarLocked') === 'true';
    const isDesktop = () => window.innerWidth >= 1024;

    function init() {
        // Hapus style nuclear jika ada
        const nuclear = document.getElementById('nuclear-sidebar-style');
        if (nuclear) nuclear.remove();

        if (isDesktop()) {
            if (isSidebarLocked) lockSidebar();
            else unlockSidebar();
        } else {
            sidebar.classList.add('close', 'mobile-closed');
            sidebar.classList.remove('locked', 'hover-expand', 'mobile-open');
        }

        setTimeout(() => sidebar.classList.remove('preload'), 300);
        restoreScrollPosition();
        updateLockIcon();
    }

    function lockSidebar() {
        isSidebarLocked = true;
        localStorage.setItem('isSidebarLocked', 'true');
        sidebar.classList.add('locked');
        sidebar.classList.remove('close', 'hover-expand');
        updateLockIcon();
    }

    function unlockSidebar() {
        isSidebarLocked = false;
        localStorage.setItem('isSidebarLocked', 'false');
        sidebar.classList.remove('locked');
        sidebar.classList.add('close');
        updateLockIcon();
    }

    function updateLockIcon() {
        if (!lockIcon) return;
        lockIcon.innerText = isSidebarLocked ? 'radio_button_checked' : 'radio_button_unchecked';
        if(isSidebarLocked) lockIcon.classList.add('text-indigo-400');
        else lockIcon.classList.remove('text-indigo-400');
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

    // Event Listeners
    sidebar.addEventListener('mouseenter', () => {
        if (isDesktop() && !isSidebarLocked) {
            sidebar.classList.add('hover-expand');
            if (lockIcon) lockIcon.style.display = 'block';
        }
    });

    sidebar.addEventListener('mouseleave', () => {
        if (isDesktop() && !isSidebarLocked) {
            sidebar.classList.remove('hover-expand');
        }
    });

    if (lockIcon) {
        lockIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isDesktop()) {
                isSidebarLocked ? unlockSidebar() : lockSidebar();
            }
        });
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleMobile);
    if (sidebarCloseMobile) sidebarCloseMobile.addEventListener('click', toggleMobile);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMobile);

    window.addEventListener('resize', () => {
        if (!isDesktop()) {
            sidebar.classList.remove('locked', 'hover-expand', 'mobile-open');
            sidebar.classList.add('close', 'mobile-closed');
            sidebarOverlay?.classList.add('hidden');
        } else {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay?.classList.add('hidden');
            isSidebarLocked ? lockSidebar() : unlockSidebar();
        }
    });

    // Submenu Logic
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

    // Scroll Logic
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

    // Jalankan init
    init();
}