/* ══════════════════════════════════════════
   FAITH CONNECT — dashboard-sidebar.js
   Open / close the retractable sidebar on mobile.

   Used by: dashboards/user.php, admin.php, leader.php
   Needs these elements on the page:
     #sidebar     the <aside class="sidebar">
     #sbBackdrop  the dark overlay behind the drawer
     #menuBtn     the ☰ button in the top bar
     .sb-close    the ✕ button inside the sidebar
══════════════════════════════════════════ */
(function () {
    var sidebar  = document.getElementById('sidebar');
    var backdrop = document.getElementById('sbBackdrop');
    var menuBtn  = document.getElementById('menuBtn');
    if (!sidebar || !backdrop || !menuBtn) return;

    var MOBILE_QUERY = window.matchMedia('(max-width: 900px)');

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('show');
        menuBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        menuBtn.setAttribute('aria-expanded', 'false');
    }

    /* ☰ button toggles the drawer */
    menuBtn.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    /* Tapping the dark overlay closes it */
    backdrop.addEventListener('click', closeSidebar);

    /* Escape key closes it */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    /* Picking a menu item (or ✕ / Sign Out) closes it.
       The page's own show() / showMessages() still run first. */
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('.nav-btn, .logout-btn, .sb-close')) {
            closeSidebar();
        }
    });

    /* Rotating the phone / widening the window back to desktop: reset */
    function onBreakpointChange(e) {
        if (!e.matches) closeSidebar();
    }
    if (MOBILE_QUERY.addEventListener) {
        MOBILE_QUERY.addEventListener('change', onBreakpointChange);
    } else {
        MOBILE_QUERY.addListener(onBreakpointChange);   /* older Safari */
    }
})();
