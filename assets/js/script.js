/* ══════════════════════════════════════════
   FAITH CONNECT — script.js
   Landing page interactions
══════════════════════════════════════════ */

/* ── Show a content section ──────────────
   id         : section div ID
   clickedBtn : the nav button that was clicked
   ─────────────────────────────────────── */
function showSection(id, clickedBtn) {

    /* Hide all sections */
    var sections = document.querySelectorAll('.section');
    for (var i = 0; i < sections.length; i++) {
        sections[i].classList.remove('active');
    }

    /* Show the selected section */
    document.getElementById(id).classList.add('active');

    /* Update active state on sidebar nav links */
    var links = document.querySelectorAll('.nav-link');
    for (var i = 0; i < links.length; i++) {
        links[i].classList.remove('active');
    }
    if (clickedBtn) {
        clickedBtn.classList.add('active');
    }

    /* On mobile — close sidebar after navigating */
    if (window.innerWidth <= 900) {
        closeSidebar();
    }

    /* Scroll to top of page */
    window.scrollTo(0, 0);
}

/* ── Music category tabs ─────────────────
   cat : category string e.g. 'meditation'
   btn : the tab button that was clicked
   ─────────────────────────────────────── */
function showMusic(cat, btn) {

    /* Hide all music panels */
    var panels = document.querySelectorAll('.music-panel');
    for (var i = 0; i < panels.length; i++) {
        panels[i].classList.remove('active');
    }

    /* Show selected panel */
    document.getElementById('music-' + cat).classList.add('active');

    /* Update active tab */
    var tabs = document.querySelectorAll('.mtab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    btn.classList.add('active');
}

/* ── Sidebar toggle (mobile) ─────────── */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}