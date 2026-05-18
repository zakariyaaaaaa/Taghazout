<?php
$current = basename($_SERVER['PHP_SELF']);
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9999;
    pointer-events: none;
    height: 70px;
}

/* Logo seul f lissr */
.nav-logo {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    pointer-events: all;
}

/* Pill f center */
.navbar-pill-wrap {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    pointer-events: all;
}

.navbar-inner {
    background: rgba(255, 255, 255, 0.15);
    padding: 2px;
    border-radius: 50px;
    border: 1px solid rgba(255, 255, 255, 0.12);
  background: transparent;

}

/* Light mode — خليها شفافة */
.navbar-content {
    background: rgba(10, 15, 25, 0.55);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 50px;
    padding: 5px 8px;
    display: flex;
    align-items: center;
    gap: 2px;
}

/* Dark mode — نفس الشيء */
[data-theme="dark"] .navbar-content {
    background: rgba(10, 15, 25, 0.55);
}
/* ⬇️ Zid had padding f body dyal koll page bash content ma itkhbash ta7t navbar */
/* body { padding-top: 80px; } */

.nav-divider {
    width: 1px;
    height: 20px;
    background: rgba(255,255,255,0.12);
    margin: 0 4px;
    flex-shrink: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 50px;
    color: #9ca3af;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.2s;
    white-space: nowrap;
}

.nav-link:hover {
    background: rgba(255,255,255,0.08);
    color: white;
}

.nav-link.active {
    background: #6366f1;
    color: white;
}

.nav-link svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.nav-icon-only {
    padding: 7px 9px;
}

/* Dropdown */
.nav-dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 0.6rem);
    right: 0;
    background: #1f2937;
    border-radius: 14px;
    padding: 6px;
    min-width: 190px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.4);
    flex-direction: column;
    gap: 2px;
}

.nav-dropdown:hover .dropdown-menu {
    display: flex;
}

.dropdown-menu a {
    padding: 8px 12px;
    color: #9ca3af;
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    transition: all 0.2s;
}

.dropdown-menu a:hover {
    background: rgba(255,255,255,0.08);
    color: white;
}

.dropdown-menu a.logout {
    color: #ef4444;
}

.dropdown-menu a.logout:hover {
    background: rgba(239,68,68,0.1);
    color: #ef4444;
}

/* Theme button */
.theme-btn {
    background: #1f2937;
    border: none;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 6px;
    transition: background 0.2s;
    flex-shrink: 0;
}

.theme-btn:hover {
    background: #374151;
}

/* ⬇️ Zid had CSS f pages diyalk (index.php, hotels.php...) bash content ma itkhbash ta7t navbar */
body {
    padding-top: 80px;
}


/* Mobile */
@media (max-width: 768px) {
    .navbar {
        height: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 8px;
        gap: 6px;
        background: #0d1520;
    }
    .nav-logo {
        position: static;
        transform: none;
    }
    .navbar-pill-wrap {
        position: static;
        transform: none;
    }
    .navbar-content {
        flex-wrap: wrap;
        justify-content: center;
    }
    .nav-divider { display: none; }
    .nav-link span { display: none; }
    .nav-link { padding: 8px 10px; }
}

</style>

<nav class="navbar">

    <!-- Logo standalone f lissr -->
<a href="../index.php" class="nav-logo">
    <img src="../assets/images/logo-white.png" class="logo-img" alt="Taghazout">
</a>

    <!-- Pill f center -->
    <div class="navbar-pill-wrap">
    <div class="navbar-inner">
        <div class="navbar-content">

            <!-- Links -->
            <a href="../index.php" class="nav-link <?= isActive('index.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span>Accueil</span>
            </a>

            <a href="../hotels.php" class="nav-link <?= isActive('hotels.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/></svg>
                <span>Hôtels</span>
            </a>

            <a href="../activities.php" class="nav-link <?= isActive('activities.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
                <span>Activités</span>
            </a>

            <a href="../surf-courses.php" class="nav-link <?= isActive('surf-courses.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 16.99c-1.35 0-2.2.42-2.95.8-.65.33-1.18.6-2.05.6-.87 0-1.4-.27-2.05-.6-.75-.38-1.6-.8-2.95-.8s-2.2.42-2.95.8c-.65.33-1.18.6-2.05.6v1.95c1.35 0 2.2-.42 2.95-.8.65-.33 1.17-.6 2.05-.6s1.4.27 2.05.6c.75.38 1.6.8 2.95.8s2.2-.42 2.95-.8c.65-.33 1.17-.6 2.05-.6s1.4.27 2.05.6c.75.38 1.6.8 2.95.8v-1.95c-.87 0-1.4-.27-2.05-.6-.75-.38-1.6-.8-2.95-.8zm0-4.45c-1.35 0-2.2.43-2.95.8-.65.32-1.18.6-2.05.6-.87 0-1.4-.28-2.05-.6-.75-.37-1.6-.8-2.95-.8s-2.2.43-2.95.8c-.65.32-1.18.6-2.05.6v1.95c1.35 0 2.2-.43 2.95-.8.65-.32 1.17-.6 2.05-.6s1.4.28 2.05.6c.75.37 1.6.8 2.95.8s2.2-.43 2.95-.8c.65-.32 1.17-.6 2.05-.6s1.4.28 2.05.6c.75.37 1.6.8 2.95.8v-1.95c-.87 0-1.4-.28-2.05-.6-.75-.37-1.6-.8-2.95-.8zM18.95 3c-1.35 0-2.2.42-2.95.8-.65.33-1.18.6-2.05.6-.87 0-1.4-.27-2.05-.6C11.15 3.42 10.3 3 8.95 3S6.75 3.42 6 3.8c-.65.33-1.18.6-2.05.6v1.95c1.35 0 2.2-.42 2.95-.8C7.55 5.22 8.08 5 8.95 5s1.4.27 2.05.6c.75.38 1.6.8 2.95.8s2.2-.42 2.95-.8c.65-.33 1.17-.6 2.05-.6s1.4.27 2.05.6c.75.38 1.6.8 2.95.8V4.6c-.87 0-1.4-.27-2.05-.6C20.15 3.62 19.3 3.2 18 3z"/></svg>
                <span>Surf</span>
            </a>

            <a href="../restaurants.php" class="nav-link <?= isActive('restaurants.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/></svg>
                <span>Restaurants</span>
            </a>

            <a href="../map/explore-map.php" class="nav-link <?= isActive('explore-map.php', $current) ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>
                <span>Carte</span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>

                <div class="nav-divider"></div>

                <a href="../notifications.php" class="nav-link nav-icon-only" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                </a>

                <a href="../favorites.php" class="nav-link nav-icon-only" title="Favoris">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                </a>

                <!-- Profil dropdown -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <span><?= htmlspecialchars($_SESSION['username'] ?? 'Profil') ?></span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../profile/profile.php">👤 Mon Profil</a>
                        <a href="../profile/my-bookings.php">📋 Mes Réservations</a>
                        <a href="../profile/loyalty-points.php">⭐ Points Fidélité</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/dashboard.php">⚙️ Dashboard Admin</a>
                        <?php endif; ?>
                        <a href="../auth/logout.php" class="logout">🚪 Se déconnecter</a>
                    </div>
                </div>

            <?php else: ?>

                <div class="nav-divider"></div>

                <a href="../auth/login.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
                    <span>Connexion</span>
                </a>

                <a href="../auth/signup.php" class="nav-link active">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <span>Inscription</span>
                </a>

            <?php endif; ?>

            <!-- Theme toggle -->
            <button class="theme-btn" id="toggleThemeBtn" title="Changer thème">☀️</button>

        </div>
    </div>
    </div><!-- end navbar-pill-wrap -->
</nav>

<script>
(function() {
    const btn = document.getElementById('toggleThemeBtn');
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    if (btn) btn.textContent = saved === 'dark' ? '🌙' : '☀️';

    if (btn) {
        btn.addEventListener('click', function() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            btn.textContent = newTheme === 'dark' ? '🌙' : '☀️';
        });
    }
})();
</script>