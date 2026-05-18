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
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text">
            <strong>Taghazout</strong>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php">
            <span class="nav-icon">📊</span><span>Dashboard</span>
        </a>
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php">
            <span class="nav-icon">🏨</span><span>Hôtels</span>
        </a>
        <a href="../activities/activities.php">
            <span class="nav-icon">🎯</span><span>Activités</span>
        </a>
        <a href="../surf-courses/courses.php">
            <span class="nav-icon">🏄</span><span>Surf Courses</span>
        </a>
        <a href="../restaurants/restaurants.php">
            <span class="nav-icon">🍽️</span><span>Restaurants</span>
        </a>
        <div class="sb-label">Gestion</div>
        <a href="bookings.php" class="active">
            <span class="nav-icon">📅</span><span>Réservations</span>
            <?php if ($stats['pending'] > 0): ?>
            <span class="nav-badge"><?= $stats['pending'] ?></span>
            <?php endif; ?>
        </a>
        <a href="../payments/payments.php">
            <span class="nav-icon">💳</span><span>Paiements</span>
        </a>
        <a href="../users/users.php">
            <span class="nav-icon">👥</span><span>Utilisateurs</span>
        </a>
        <a href="../reviews/reviews.php">
            <span class="nav-icon">⭐</span><span>Avis</span>
        </a>
        <div class="sb-label">Paramètres</div>
        <a href="../../index.php">
            <span class="nav-icon">🌐</span><span>Voir le site</span>
        </a>
        <a href="../../auth/logout.php">
            <span class="nav-icon">🚪</span><span>Déconnexion</span>
        </a>
    </nav>

    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
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