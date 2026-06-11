<?php
// ─── Auto-detect active page ───────────────────────────────
$_cur_page = basename($_SERVER['PHP_SELF']);
$_cur_dir  = basename(dirname($_SERVER['PHP_SELF']));

function sb_active(string $dir, string $file = ''): string {
    global $_cur_page, $_cur_dir;
    if ($file) return ($_cur_page === $file && $_cur_dir === $dir) ? ' class="active"' : '';
    return $_cur_dir === $dir ? ' class="active"' : '';
}

// ─── Pending bookings badge ────────────────────────────────
global $pdo;
$_pending = 0;
try {
    $_pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
} catch(Exception $e) {}
?>
<aside class="admin-sidebar">

    <!-- LOGO -->
    <div class="sb-logo">
        <div class="sb-logo-mark">
            <a href="/admin/dashboard.php">
                <img src="/assets/images/logo.png" alt="Logo"
                     style="width:40px;height:40px;border-radius:10px;display:block;">
            </a>
        </div>
        <div class="sb-logo-text">
            <a href="/admin/dashboard.php" style="text-decoration:none;color:inherit;">
                <strong>Taghazout</strong>
                <span>Admin Panel</span>
            </a>
        </div>
    </div>

    <!-- NAV -->
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">

        <a href="/admin/dashboard.php"<?= sb_active('admin', 'dashboard.php') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </span>
            <span>Dashboard</span>
        </a>

        <a href="/admin/analytics.php"<?= sb_active('admin', 'analytics.php') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
                </svg>
            </span>
            <span>Analytics</span>
        </a>

        <div class="sb-label">Contenu</div>

        <a href="/admin/hotels/hotels.php"<?= sb_active('hotels') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 22V12h6v10"/><rect x="9" y="7" width="2" height="2"/><rect x="13" y="7" width="2" height="2"/>
                </svg>
            </span>
            <span>Hôtels</span>
        </a>

        <a href="/admin/activities/activities.php"<?= sb_active('activities') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
            </span>
            <span>Activités</span>
        </a>

        <a href="/admin/surf-courses/courses.php"<?= sb_active('surf-courses') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 10 Q5 6 8 10 Q11 14 14 10 Q17 6 20 10 Q22 12 24 10"/>
                    <path d="M2 16 Q5 12 8 16 Q11 20 14 16 Q17 12 20 16 Q22 18 24 16"/>
                </svg>
            </span>
            <span>Surf Courses</span>
        </a>

        <a href="/admin/restaurants/restaurants.php"<?= sb_active('restaurants') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                </svg>
            </span>
            <span>Restaurants</span>
        </a>

        <div class="sb-label">Gestion</div>

        <a href="/admin/bookings/bookings.php"<?= sb_active('bookings') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                </svg>
            </span>
            <span>Réservations</span>
            <?php if ($_pending > 0): ?>
            <span class="nav-badge"><?= $_pending ?></span>
            <?php endif; ?>
        </a>

        <a href="/admin/payments/payments.php"<?= sb_active('payments') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </span>
            <span>Paiements</span>
        </a>

        <a href="/admin/users/users.php"<?= sb_active('users') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            <span>Utilisateurs</span>
        </a>

        <a href="/admin/reviews/reviews.php"<?= sb_active('reviews') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </span>
            <span>Avis</span>
        </a>

        <a href="/admin/messages/messages.php"<?= sb_active('messages') ?>>
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </span>
            <span>Messages</span>
        </a>

        <div class="sb-label">Paramètres</div>

        <a href="/index.php">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </span>
            <span>Voir le site</span>
        </a>

        <a href="/auth/logout.php">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </span>
            <span>Déconnexion</span>
        </a>

    </nav>

    <!-- ADMIN FOOTER -->
    <div class="sb-admin">
        <img src="/assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="/auth/logout.php" class="sb-logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>

</aside>