// resources/js/components/sidebar.js

export function initSidebarLogic() {
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.querySelector('.main-wrapper'); 
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    const toggleIcon = document.getElementById('toggle-icon');
    
    // Mobile Elements
    const mobileOverlay = document.getElementById('sidebar-overlay');
    const mobileToggleBtn = document.getElementById('sidebar-toggle-mobile');

    if (!sidebar) return;

    // --- 1. Load State ---
    let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    // --- 2. Function Apply Layout ---
    const applySidebarState = () => {
        const isDesktop = window.innerWidth >= 1024;

        if (isDesktop) {
            if (isCollapsed) {
                // Mode Collapse
                sidebar.classList.add('collapsed');
                if (mainContent) mainContent.style.marginLeft = '70px';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
                
                // Tutup semua submenu (accordion style) saat masuk mode collapse
                document.querySelectorAll('.submenu').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.dropdown-arrow').forEach(el => el.classList.remove('rotate-180'));
            } else {
                // Mode Expand
                sidebar.classList.remove('collapsed');
                if (mainContent) mainContent.style.marginLeft = '260px';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
            }
        } else {
            // Mobile Mode
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.style.marginLeft = '0';
        }
    };

    // --- 3. Toggle Button Event ---
    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            isCollapsed = !isCollapsed;
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            applySidebarState();
        });
    }

    // --- 4. DROPDOWN CLICK LOGIC ---
    const sidebarScroll = document.querySelector('.sidebar-scroll');
    if (sidebarScroll) {
        sidebarScroll.addEventListener('click', function(e) {
            // Cari elemen tombol dropdown
            const toggleLink = e.target.closest('.sidebar-dropdown-toggle');
            
            if (toggleLink) {
                e.preventDefault();

                // LOGIKA: Jika sidebar sedang COLLAPSED, buka sidebar dulu
                if (sidebar.classList.contains('collapsed') && window.innerWidth >= 1024) {
                    // 1. Ubah state jadi expand
                    isCollapsed = false;
                    localStorage.setItem('sidebarCollapsed', 'false');
                    applySidebarState(); 

                    // 2. Buka menu target secara otomatis (setelah jeda sangat singkat agar transisi smooth)
                    setTimeout(() => {
                        const parentLi = toggleLink.closest('li');
                        const submenu = parentLi.querySelector('.submenu');
                        const arrow = toggleLink.querySelector('.dropdown-arrow');
                        
                        if (submenu) {
                            submenu.classList.remove('hidden');
                            if (arrow) arrow.classList.add('rotate-180');
                        }
                    }, 50);

                    return;
                }

                // Jika sidebar sudah TERBUKA, toggle seperti biasa (Accordion)
                const parentLi = toggleLink.closest('li');
                const submenu = parentLi.querySelector('.submenu');
                const arrow = toggleLink.querySelector('.dropdown-arrow');

                if (submenu) {
                    submenu.classList.toggle('hidden');
                    if (arrow) arrow.classList.toggle('rotate-180');
                }
            }
        });
    }

    // --- 5. Mobile Logic ---
    const toggleMobile = () => {
        sidebar.classList.toggle('mobile-open');
        if (mobileOverlay) mobileOverlay.classList.toggle('hidden');
    };

    if (mobileToggleBtn) mobileToggleBtn.addEventListener('click', toggleMobile);
    if (mobileOverlay) mobileOverlay.addEventListener('click', toggleMobile);

    // --- 6. Init ---
    applySidebarState();
    window.addEventListener('resize', applySidebarState);
    
    // Transisi konten
    setTimeout(() => {
        if (mainContent) mainContent.classList.add('transition-all', 'duration-300');
    }, 100);
}