// resources/js/components/sidebar.js

export function initSidebarLogic() {
    const body = document.body;
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtnDesktop = document.getElementById('toggle-desktop');
    const toggleBtnMobile = document.getElementById('sidebar-toggle');
    const sidebarScroll = document.querySelector('.sidebar-scroll');

    // --- ELEMENT PROFIL BARU ---
    const profileTrigger = document.getElementById('profile-trigger-btn'); // Tombol yang diklik
    const profilePopup = document.getElementById('profile-popup');         // Popup menu

    if (!sidebar) return;
    
    // --- 1. STATE INITIALIZATION ---
    let isCompact = localStorage.getItem('sidebarState') === 'compact';

    function applyState() {
        if (window.innerWidth >= 1024) {
            if (isCompact) {
                body.classList.add('layout-compact');
                closeAllSubmenus();
            } else {
                body.classList.remove('layout-compact');
            }
            body.classList.remove('mobile-open');
        } else {
            body.classList.remove('layout-compact');
        }
    }
    applyState();

    // --- 2. TOGGLE SIDEBAR ---
    if (toggleBtnDesktop) {
        toggleBtnDesktop.addEventListener('click', (e) => {
            e.preventDefault();
            isCompact = !isCompact;
            localStorage.setItem('sidebarState', isCompact ? 'compact' : 'expanded');
            applyState();
        });
    }

    function toggleMobile() {
        body.classList.toggle('mobile-open');
        if (overlay) overlay.classList.toggle('hidden');
    }

    if (toggleBtnMobile) toggleBtnMobile.addEventListener('click', toggleMobile);
    if (overlay) overlay.addEventListener('click', toggleMobile);

    // --- 3. SUBMENU LOGIC ---
    function closeAllSubmenus() {
        document.querySelectorAll('.submenu-wrapper').forEach(el => {
            el.classList.remove('open');
            el.style.maxHeight = '0px';
        });
        document.querySelectorAll('.dropdown-toggle').forEach(el => {
            el.classList.remove('active-parent');
            const arrow = el.querySelector('.arrow-icon');
            if(arrow) arrow.classList.remove('rotate-180');
        });
    }

    if (sidebarScroll) {
        sidebarScroll.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('.dropdown-toggle');
            if (toggleBtn) {
                e.preventDefault();
                if (body.classList.contains('layout-compact')) {
                    isCompact = false;
                    localStorage.setItem('sidebarState', 'expanded');
                    applyState();
                    setTimeout(() => toggleMenuLogic(toggleBtn), 200);
                } else {
                    toggleMenuLogic(toggleBtn);
                }
            }
        });
    }

    function toggleMenuLogic(btn) {
        const parentLi = btn.closest('li');
        const submenu = parentLi.querySelector('.submenu-wrapper');
        const arrow = btn.querySelector('.arrow-icon');

        if (submenu) {
            const isOpen = submenu.classList.contains('open');
            if (!isOpen) closeAllSubmenus(); 

            if (!isOpen) {
                submenu.classList.add('open');
                submenu.style.maxHeight = submenu.scrollHeight + "px";
                if (arrow) arrow.classList.add('rotate-180');
                btn.classList.add('active-parent');
            } else {
                submenu.classList.remove('open');
                submenu.style.maxHeight = '0px';
                if (arrow) arrow.classList.remove('rotate-180');
                btn.classList.remove('active-parent');
            }
        }
    }

    // --- 4. PROFILE POPUP ANIMATION LOGIC (NEW) ---
    if (profileTrigger && profilePopup) {
        // Toggle saat diklik
        profileTrigger.addEventListener('click', (e) => {
            e.stopPropagation(); // Mencegah event bubbling
            profilePopup.classList.toggle('show');
        });

        // Tutup saat klik di luar (Outside Click)
        document.addEventListener('click', (e) => {
            // Jika yang diklik BUKAN popup DAN BUKAN tombol trigger
            if (!profilePopup.contains(e.target) && !profileTrigger.contains(e.target)) {
                profilePopup.classList.remove('show');
            }
        });
    }

    // --- 5. UTILS ---
    window.addEventListener('resize', applyState);
    
    const savedScroll = localStorage.getItem('sidebarScrollPosition');
    if (savedScroll && sidebarScroll) { sidebarScroll.scrollTop = parseInt(savedScroll); }
    
    window.addEventListener('beforeunload', () => {
        if(sidebarScroll) localStorage.setItem('sidebarScrollPosition', sidebarScroll.scrollTop);
    });
}