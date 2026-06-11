<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// ─── Filters ──────────────────────────────────────────────
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type   = isset($_GET['type'])   ? trim($_GET['type'])   : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";

if ($status !== '') {
    $s = $pdo->quote($status);
    $where .= " AND b.status = $s";
}
if ($type !== '') {
    $t = $pdo->quote($type);
    $where .= " AND b.type = $t";
}
if ($search !== '') {
    $q = $pdo->quote("%$search%");
    $where .= " AND (u.name LIKE $q OR u.email LIKE $q)";
}

// ─── Pagination ───────────────────────────────────────────
$per_page = 15;
$page     = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

$total = $pdo->query("
    SELECT COUNT(*) FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    $where
")->fetchColumn();

$total_pages = ceil($total / $per_page);

$bookings = $pdo->query("
    SELECT b.*,
        u.name AS user_name,
        u.email AS user_email,
        COALESCE(h.name, s.title) AS item_name,
        COALESCE(h.image, s.image) AS item_image
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN hotels h ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf' AND b.reference_id = s.id
    $where
    ORDER BY b.created_at DESC
    LIMIT $per_page OFFSET $offset
")->fetchAll();

// ─── Stats ────────────────────────────────────────────────
$stats = [
    'total'    => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'pending'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'accepted' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'accepted'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'rejected'")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'accepted'")->fetchColumn() ?? 0,
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sb-logo">
        <div class="sb-logo-mark">
            <!-- Surf/Wave icon -->
     <div class="sb-logo-mark">
            <a href="../dashboard.php">
                <img src="../../assets/images/logo.png" alt="" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="border-radius: 10px;">
        </a>
    </div>
    </div>
        <div class="sb-logo-text">
        <a href="../dashboard.php">
            <strong>Taghazout</strong>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
            <span class="nav-icon">
                <!-- BarChart2 -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </span>
            <span>Dashboard</span>
        </a>
        <a href="../analytics.php">
            <span class="nav-icon">
                <!-- TrendingUp -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
                </svg>
            </span>
            <span>Analytics</span>
        </a>

        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php">
            <span class="nav-icon">
                <!-- Building2 -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 22V12h6v10"/><rect x="9" y="7" width="2" height="2"/><rect x="13" y="7" width="2" height="2"/>
                </svg>
            </span>
            <span>Hôtels</span>
        </a>
        <a href="../activities/activities.php">
            <span class="nav-icon">
                <!-- Zap -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
            </span>
            <span>Activités</span>
        </a>
        <a href="../surf-courses/courses.php">
            <span class="nav-icon">
                <!-- Waves -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 10 Q5 6 8 10 Q11 14 14 10 Q17 6 20 10 Q22 12 24 10"/>
                    <path d="M2 16 Q5 12 8 16 Q11 20 14 16 Q17 12 20 16 Q22 18 24 16"/>
                </svg>
            </span>
            <span>Surf Courses</span>
        </a>
        <a href="../restaurants/restaurants.php">
            <span class="nav-icon">
                <!-- UtensilsCrossed -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                </svg>
            </span>
            <span>Restaurants</span>
        </a>

        <div class="sb-label">Gestion</div>
        <a href="bookings.php" class="active">
            <span class="nav-icon">
                <!-- CalendarDays -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                </svg>
            </span>
            <span>Réservations</span>
            <?php if ($stats['pending'] > 0): ?>
            <span class="nav-badge"><?= $stats['pending'] ?></span>
            <?php endif; ?>
        </a>
        <a href="../payments/payments.php">
            <span class="nav-icon">
                <!-- CreditCard -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </span>
            <span>Paiements</span>
        </a>
        <a href="../users/users.php">
            <span class="nav-icon">
                <!-- Users -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            <span>Utilisateurs</span>
        </a>
        <a href="../reviews/reviews.php">
            <span class="nav-icon">
                <!-- Star -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </span>
            <span>Avis</span>
        </a>
        <a href="../messages/messages.php">
            <span class="nav-icon">
                <!-- MessageSquare -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </span>
            <span>Messages</span>
        </a>

        <div class="sb-label">Paramètres</div>
        <a href="../../index.php">
            <span class="nav-icon">
                <!-- Globe -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </span>
            <span>Voir le site</span>
        </a>
        <a href="../../auth/logout.php">
            <span class="nav-icon">
                <!-- LogOut -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </span>
            <span>Déconnexion</span>
        </a>
    </nav>

    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../../auth/logout.php" class="sb-logout">
            <!-- LogOut small -->
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="admin-main">

    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Réservations</div>
                <div class="topbar-breadcrumb">
                    <a href="../dashboard.php">Home</a>
                    <span>/</span> Réservations
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1>📅 Réservations</h1>
                <p>Gérez et confirmez les réservations des clients</p>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 2rem;">
            <div class="stat-card info">
                <div class="stat-icon" style="background:var(--color-info-bg);">📋</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card sand">
                <div class="stat-icon" style="background:var(--color-warning-bg);">⏳</div>
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon" style="background:var(--color-success-bg);">✅</div>
                <div class="stat-value"><?= $stats['accepted'] ?></div>
                <div class="stat-label">Confirmées</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon" style="background:var(--color-danger-bg);">❌</div>
                <div class="stat-value"><?= $stats['rejected'] ?></div>
                <div class="stat-label">Refusées</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon" style="background:var(--color-success-bg);">💰</div>
                <div class="stat-value"><?= number_format($stats['revenue'], 0, ',', ' ') ?></div>
                <div class="stat-label">Revenus MAD</div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-body" style="padding:1.2rem 1.5rem;">
                <form method="GET" style="display:flex; gap:0.8rem; flex-wrap:wrap; align-items:center;">

                    <div style="position:relative; flex:1; min-width:200px;">
                        <span style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); opacity:0.4;">🔍</span>
                        <input type="text" name="search" placeholder="Rechercher client..."
                            value="<?= htmlspecialchars($search) ?>"
                            style="width:100%; height:40px; padding:0 1rem 0 2.5rem; border:1.5px solid var(--border-mid); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:0.875rem; background:var(--bg-surface); color:var(--text-primary); outline:none;">
                    </div>

                    <select name="status" onchange="this.form.submit()"
                        style="height:40px; padding:0 1rem; border:1.5px solid var(--border-mid); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:0.875rem; background:var(--bg-surface); color:var(--text-primary); outline:none; cursor:pointer;">
                        <option value="">Tous les statuts</option>
                        <option value="pending"  <?= $status === 'pending'  ? 'selected' : '' ?>>⏳ En attente</option>
                        <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>✅ Confirmées</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>❌ Refusées</option>
                    </select>

                    <select name="type" onchange="this.form.submit()"
                        style="height:40px; padding:0 1rem; border:1.5px solid var(--border-mid); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:0.875rem; background:var(--bg-surface); color:var(--text-primary); outline:none; cursor:pointer;">
                        <option value="">Tous les types</option>
                        <option value="hotel" <?= $type === 'hotel' ? 'selected' : '' ?>>🏨 Hôtels</option>
                        <option value="surf"  <?= $type === 'surf'  ? 'selected' : '' ?>>🏄 Surf</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>

                    <?php if ($status || $type || $search): ?>
                    <a href="bookings.php" class="btn btn-secondary btn-sm">✕ Reset</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Liste des réservations</h3>
                <span style="font-size:0.82rem; color:var(--text-muted);">
                    <?= $total ?> réservation<?= $total > 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Hébergement</th>
                            <th>Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Nuits</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <span class="empty-icon">📅</span>
                                    <h4>Aucune réservation trouvée</h4>
                                    <p>Modifiez vos filtres de recherche.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $b):
                            $nights = (new DateTime($b['check_in']))->diff(new DateTime($b['check_out']))->days;
                            $statusClass = ['pending' => 'badge-warning', 'accepted' => 'badge-success', 'rejected' => 'badge-danger'][$b['status']] ?? 'badge-neutral';
                            $statusLabel = ['pending' => '⏳ En attente', 'accepted' => '✅ Confirmée', 'rejected' => '❌ Refusée'][$b['status']] ?? $b['status'];
                        ?>
                        <tr>
                            <td style="font-weight:700; color:var(--ocean-teal);">#<?= $b['id'] ?></td>
                            <td>
                                <div class="td-user">
                                    <div class="td-avatar-initials">
                                        <?= strtoupper(substr($b['user_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="td-name"><?= htmlspecialchars($b['user_name'] ?? 'N/A') ?></div>
                                        <div class="td-sub"><?= htmlspecialchars($b['user_email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    <img src="../../uploads/<?= $b['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($b['item_image'] ?? '') ?>"
                                        onerror="this.src='../../assets/images/default.jpg'"
                                        style="width:36px; height:30px; border-radius:6px; object-fit:cover; background:var(--bg-surface);">
                                    <span style="font-weight:500; color:var(--text-primary); font-size:0.875rem;">
                                        <?= htmlspecialchars($b['item_name'] ?? 'N/A') ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= $b['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem; color:var(--text-secondary);">
                                <?= date('d/m/Y', strtotime($b['check_in'])) ?>
                            </td>
                            <td style="font-size:0.82rem; color:var(--text-secondary);">
                                <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                            </td>
                            <td style="text-align:center; font-weight:600; color:var(--text-primary);">
                                <?= $nights ?>
                            </td>
                            <td style="font-weight:700; color:var(--ocean-teal);">
                                <?= number_format($b['total_price'], 0, ',', ' ') ?> MAD
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.4rem;">
                                    <?php if ($b['status'] === 'pending'): ?>
                                    <a href="accept-booking.php?id=<?= $b['id'] ?>"
                                        onclick="return confirm('Confirmer cette réservation ?')"
                                        class="btn btn-success btn-sm">✅</a>
                                    <a href="reject-booking.php?id=<?= $b['id'] ?>"
                                        onclick="return confirm('Refuser cette réservation ?')"
                                        class="btn btn-danger btn-sm">❌</a>
                                    <?php endif; ?>
                                    <a href="booking-details.php?id=<?= $b['id'] ?>"
                                        class="btn btn-secondary btn-sm">👁️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div style="padding:1.2rem 1.5rem; border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.82rem; color:var(--text-muted);">
                    Page <?= $page ?> sur <?= $total_pages ?>
                </span>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">←</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>
</div>
</body>
</html>