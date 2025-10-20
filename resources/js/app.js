import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* =========================================== */
/* === KODE SIDEBAR DENGAN LOCALSTORAGE    === */
/* =========================================== */
document.addEventListener('DOMContentLoaded', function() {

    // Memilih elemen sidebar dan tombol
    const sidebar = document.querySelector(".sidebar"); // Bisa admin atau client
    const sidebarOpenBtn = document.querySelector("#sidebar-open"); // Floating button (hanya ada di satu layout)
    const sidebarCloseBtn = sidebar ? sidebar.querySelector("#sidebar-close") : null; // Tombol X di dalam sidebar
    const sidebarLockBtn = sidebar ? sidebar.querySelector("#lock-icon") : null; // Tombol Lock (hanya ada di sidebar admin)
    const mainWrapper = document.querySelector(".main-wrapper");
    const overlay = document.querySelector(".sidebar-overlay");

    // Hanya jalankan jika sidebar ditemukan di halaman ini
    if (sidebar) {

        // --- Logika Load State localStorage (Hanya jika ada tombol lock) ---
        if (sidebarLockBtn) {
            const isSidebarLocked = localStorage.getItem("sidebarLocked") === "true";
            if (isSidebarLocked) {
                sidebar.classList.add("locked");
                sidebar.classList.remove("hoverable", "close");
                sidebarLockBtn.classList.replace("bx-lock-open-alt", "bx-lock-alt");
            } else {
                sidebar.classList.remove("locked");
                sidebar.classList.add("hoverable", "close");
                sidebarLockBtn.classList.replace("bx-lock-alt", "bx-lock-open-alt");
            }
        } else {
             // Jika tidak ada tombol lock (sidebar client), default ke hoverable/close
             sidebar.classList.add("hoverable", "close");
             sidebar.classList.remove("locked"); // Pastikan tidak locked
        }
        // --- Akhir Logika Load State ---


        // --- Fungsi toggleLock (Hanya jika ada tombol lock) ---
        const toggleLock = () => {
            if (!sidebar || !sidebarLockBtn) return; // Perlu sidebar & tombol lock
            sidebar.classList.toggle("locked");
            if (sidebar.classList.contains("locked")) {
                localStorage.setItem("sidebarLocked", "true");
                sidebar.classList.remove("hoverable", "close");
                sidebarLockBtn.classList.replace("bx-lock-open-alt", "bx-lock-alt");
            } else {
                localStorage.setItem("sidebarLocked", "false");
                sidebar.classList.add("hoverable");
                sidebarLockBtn.classList.replace("bx-lock-alt", "bx-lock-open-alt");
            }
        };

        // --- Fungsi hideSidebar (Hanya jika tidak di-lock) ---
        const hideSidebar = () => {
            // Cek apakah ada lock button DAN sidebar locked
            const isLocked = sidebarLockBtn && sidebar.classList.contains("locked");
            if (sidebar && !isLocked && sidebar.classList.contains("hoverable")) {
                sidebar.classList.add("close");
            }
        };

        // --- Fungsi showSidebar (Hanya jika tidak di-lock) ---
        const showSidebar = () => {
           const isLocked = sidebarLockBtn && sidebar.classList.contains("locked");
           if (sidebar && !isLocked && sidebar.classList.contains("hoverable")) {
                sidebar.classList.remove("close");
            }
        };

        // --- Fungsi toggleSidebar (Mobile) ---
        const toggleSidebar = () => {
            console.log("Toggle Sidebar Button Clicked!");
            if (!sidebar) return;

            if (window.innerWidth < 992) {
                sidebar.classList.remove("locked", "hoverable", "close");
            }
            sidebar.classList.toggle("open");
            console.log("Sidebar classes:", sidebar.classList);

            if (overlay) {
                overlay.classList.toggle("active", sidebar.classList.contains("open"));
            }
        };

        // --- Cek layar mobile saat load ---
        if (window.innerWidth < 992) {
            sidebar.classList.add("close");
            sidebar.classList.remove("locked", "hoverable", "open");
        }

        // --- Tambahkan event listeners ---
        if (sidebarLockBtn) sidebarLockBtn.addEventListener("click", toggleLock); // Hanya jika ada
        sidebar.addEventListener("mouseleave", hideSidebar);
        sidebar.addEventListener("mouseenter", showSidebar);

        // Listener untuk tombol Buka (Hamburger) - Bisa ada di layout admin atau client
        if (sidebarOpenBtn) {
            console.log("Sidebar open button found, adding listener.");
            sidebarOpenBtn.addEventListener("click", toggleSidebar);
        } else {
             // Jika tidak ada #sidebar-open (cth: di layout admin lama), mungkin ada trigger lain?
             // Atau mungkin tidak ada tombol buka di layout itu.
             // console.warn("Sidebar open button (#sidebar-open) not found on this layout.");
        }
        // Listener untuk tombol Tutup (X di dalam sidebar)
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener("click", toggleSidebar);


        // --- Listener Klik Di Luar Sidebar ---
        const closeSidebarOnClickOutside = (event) => {
            // Cek tombol open button ADA atau TIDAK
            const clickedOpenButton = sidebarOpenBtn && sidebarOpenBtn.contains(event.target);

            if (sidebar &&
                sidebar.classList.contains("open") &&
                !sidebar.contains(event.target) &&
                !clickedOpenButton )
            {
                console.log("Clicked outside sidebar, closing.");
                toggleSidebar();
            }
        };
        if (overlay) {
            console.log("Overlay found, adding click listener.");
            overlay.addEventListener("click", closeSidebarOnClickOutside);
        } else if (mainWrapper) {
            console.log("Overlay not found, adding click listener to mainWrapper.");
            mainWrapper.addEventListener("click", closeSidebarOnClickOutside);
        }
        // --- Akhir Listener Klik Di Luar ---

    } // End if (sidebar)

});
/* === AKHIR KODE SIDEBAR === */