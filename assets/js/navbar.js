/* ═══════════════════════════════════════════════════════════
   navbar.js — Taghazout Navbar Scripts
═══════════════════════════════════════════════════════════ */

// ── Scroll effect ──────────────────────────────────────────
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar?.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });

// ── Dropdown ───────────────────────────────────────────────
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const btn      = dropdown?.querySelector('.nav-avatar-btn');
    const isOpen   = dropdown?.classList.toggle('open');
    btn?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        dropdown.querySelector('.nav-avatar-btn')?.setAttribute('aria-expanded', 'false');
    }
});

// Close dropdown on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('userDropdown')?.classList.remove('open');
        closeMobileMenu();
    }
});

// ── Mobile menu ────────────────────────────────────────────
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const hamburger = document.getElementById('hamburger');
    const overlay   = document.getElementById('mobileOverlay');
    const isOpen    = navLinks?.classList.toggle('open');
    hamburger?.classList.toggle('open', isOpen);
    overlay?.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeMobileMenu() {
    document.querySelector('.nav-links')?.classList.remove('open');
    document.getElementById('hamburger')?.classList.remove('open');
    document.getElementById('mobileOverlay')?.classList.remove('open');
    document.body.style.overflow = '';
}
