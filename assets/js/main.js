

// ==============================
// NAVBAR SCROLL
// ==============================
const navbar = document.querySelector('.navbar');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.scrollY;

    if (currentScroll > 80) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }

    // Hide/show navbar on scroll
    if (currentScroll > lastScroll && currentScroll > 200) {
        navbar.style.transform = 'translateY(-100%)';
    } else {
        navbar.style.transform = 'translateY(0)';
    }

    lastScroll = currentScroll;
}, { passive: true });

// ==============================
// HERO PARALLAX
// ==============================
const heroContent = document.querySelector('.hero-content');
const heroVideo = document.querySelector('.hero-video');

window.addEventListener('scroll', () => {
    const scrollY = window.scrollY;
    if (heroContent) {
        heroContent.style.transform = `translateY(${scrollY * 0.1}px)`;
        heroContent.style.opacity = Math.max(0, 1 - scrollY / 200);
    }
    if (heroVideo) {
        heroVideo.style.transform = `translateY(${scrollY * 0.15}px) scale(1.1)`;
    }
}, { passive: true });


// ==============================
// STATS COUNTER
// ==============================
function animateCounter(el, target, suffix) {
    let start = 0;
    const duration = 2500;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Easing
        const eased = 1 - Math.pow(1 - progress, 4);
        start = Math.floor(eased * target);
        el.textContent = start.toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}

const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('.stat-item h2').forEach(stat => {
                const text = stat.textContent.trim();
                const num = parseInt(text.replace(/[^0-9]/g, ''));
                const suffix = text.replace(/[0-9]/g, '');
                animateCounter(stat, num, suffix);
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const statsSection = document.querySelector('.stats');
if (statsSection) statsObserver.observe(statsSection);

// ==============================
// CARD 3D HOVER
// ==============================
document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        const rotX = ((y - cy) / cy) * 8;
        const rotY = ((cx - x) / cx) * 8;

        card.style.transform = `perspective(1000px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateY(-12px) scale(1.02)`;
        card.style.transition = 'transform 0.1s ease';

        // Shine effect
        const shine = card.querySelector('.card-shine') || (() => {
            const s = document.createElement('div');
            s.className = 'card-shine';
            s.style.cssText = `
                position: absolute; inset: 0; border-radius: inherit;
                pointer-events: none; z-index: 10;
                background: radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.15) 0%, transparent 60%);
                transition: opacity 0.3s;
            `;
            card.style.position = 'relative';
            card.appendChild(s);
            return s;
        })();

        shine.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.15) 0%, transparent 60%)`;
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0) scale(1)';
        card.style.transition = 'transform 0.5s ease';
        const shine = card.querySelector('.card-shine');
        if (shine) shine.style.opacity = '0';
    });
});

// ==============================
// SMOOTH SCROLL
// ==============================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ==============================
// SEARCH BAR FOCUS EFFECT
// ==============================
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('focus', () => {
        searchInput.parentElement.style.transform = 'scale(1.02)';
        searchInput.parentElement.style.boxShadow = '0 8px 30px rgba(0,198,224,0.3)';
    });
    searchInput.addEventListener('blur', () => {
        searchInput.parentElement.style.transform = 'scale(1)';
        searchInput.parentElement.style.boxShadow = 'none';
    });
}

// ==============================
// PAGE LOADER
// ==============================
window.addEventListener('load', () => {
    const loader = document.querySelector('.page-loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.remove(), 500);
    }

    // Animate hero content on load
    if (heroContent) {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(60px)';
        heroContent.style.transition = 'opacity 1s ease, transform 1s ease';
        setTimeout(() => {
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    }
});

// ==============================
// RIPPLE EFFECT ON BUTTONS
// ==============================
document.querySelectorAll('.btn-primary, .btn-card, .btn-register').forEach(btn => {
    btn.addEventListener('click', function (e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px; height: ${size}px;
            left: ${x}px; top: ${y}px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;

        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

// ==============================
// CSS KEYFRAMES (inject)
// ==============================
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to { transform: scale(4); opacity: 0; }
    }
    .navbar {
        transition: transform 0.3s ease, box-shadow 0.3s ease, padding 0.3s ease !important;
    }
    .navbar.scrolled {
        box-shadow: 0 4px 30px rgba(0,198,224,0.15);
        padding-top: 0.7rem !important;
        padding-bottom: 0.7rem !important;
    }
`;
document.head.appendChild(style);