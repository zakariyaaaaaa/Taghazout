<?php
session_start();
require_once '../../includes/config.php';

// ─── Admin check ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// ─── Handle delete ────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Delete image file
    $img = $pdo->prepare("SELECT image FROM hotels WHERE id = :id");
    $img->execute([':id' => $del_id]);
    $img_row = $img->fetch();
    if ($img_row && $img_row['image']) {
        $img_path = '../../uploads/hotels/' . $img_row['image'];
        if (file_exists($img_path)) unlink($img_path);
    }
    $pdo->prepare("DELETE FROM hotels WHERE id = :id")->execute([':id' => $del_id]);
    header("Location: hotels.php?deleted=1");
    exit;
}

// ─── Filters ──────────────────────────────────────────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type'])   ? trim($_GET['type'])   : '';
$sort   = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'id_desc';

$where = "WHERE 1=1";
if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (name LIKE $s OR location LIKE $s)";
}
if ($type !== '') {
    $where .= " AND type = " . $pdo->quote($type);
}

$order_map = [
    'id_desc'      => 'id DESC',
    'id_asc'       => 'id ASC',
    'name_asc'     => 'name ASC',
    'price_asc'    => 'price ASC',
    'price_desc'   => 'price DESC',
    'rating_desc'  => 'rating DESC',
];
$order = $order_map[$sort] ?? 'id DESC';

// ─── Pagination ───────────────────────────────────────────
$per_page    = 10;
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $per_page;
$total       = $pdo->query("SELECT COUNT(*) FROM hotels $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$hotels      = $pdo->query("SELECT * FROM hotels $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();

// ─── Types ────────────────────────────────────────────────
$types = $pdo->query("SELECT DISTINCT type FROM hotels WHERE type IS NOT NULL ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hôtels — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>

<div class="admin-shell">

<!-- ══════════════════════════════
     SIDEBAR
══════════════════════════════ -->
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
        <a href="../analytics.php">
            <span class="nav-icon">📈</span><span>Analytics</span>
        </a>

        <div class="sb-label">Contenu</div>
        <a href="hotels.php" class="active">
            <span class="nav-icon">🏨</span><span>Hotels</span>
        </a>
        <a href="../activities/activities.php">
            <span class="nav-icon">🎯</span><span>Activities</span>
        </a>
        <a href="../surf-courses/courses.php">
            <span class="nav-icon">🏄</span><span>Surf Courses</span>
        </a>
        <a href="../restaurants/restaurants.php">
            <span class="nav-icon">🍽️</span><span>Restaurants</span>
        </a>

        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php">
            <span class="nav-icon">📅</span><span>Bookings</span>
        </a>
        <a href="../payments/payments.php">
            <span class="nav-icon">💳</span><span>Payments</span>
        </a>
        <a href="../users/users.php">
            <span class="nav-icon">👥</span><span>Users</span>
        </a>
        <a href="../reviews/reviews.php">
            <span class="nav-icon">⭐</span><span>Reviews</span>
        </a>
        <a href="../messages/messages.php">
            <span class="nav-icon">💬</span><span>Messages</span>
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

<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="admin-main">

<div class="admin-topbar">
    <div class="topbar-left">
        <div>
            <div class="topbar-title">Hôtels</div>
            <div class="topbar-breadcrumb">
                Home <span>/</span> Contenu <span>/</span> Hôtels
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
            <h1>🏨 Gestion des Hôtels</h1>
            <p><?= $total ?> hôtel<?= $total > 1 ? 's' : '' ?> au total</p>
        </div>
        <a href="add-hotel.php" class="btn-primary">+ Ajouter un hôtel</a>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert-success" style="margin-bottom:1.25rem;">✅ Hôtel supprimé avec succès.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
    <div class="alert-success" style="margin-bottom:1.25rem;">✅ Hôtel enregistré avec succès.</div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" action="hotels.php" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">

            <div style="flex:1; min-width:200px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Rechercher</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:var(--text-light);">🔍</span>
                    <input
                        type="text"
                        name="search"
                        placeholder="Nom, localisation..."
                        value="<?= htmlspecialchars($search) ?>"
                        style="width:100%; height:42px; padding:0 1rem 0 2.5rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; transition:var(--transition);"
                    >
                </div>
            </div>

            <?php if (!empty($types)): ?>
            <div style="min-width:160px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Type</label>
                <select name="type" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="min-width:160px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Trier par</label>
                <select name="sort" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="id_desc"     <?= $sort === 'id_desc'     ? 'selected' : '' ?>>Plus récents</option>
                    <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Nom A→Z</option>
                    <option value="price_asc"   <?= $sort === 'price_asc'   ? 'selected' : '' ?>>Prix croissant</option>
                    <option value="price_desc"  <?= $sort === 'price_desc'  ? 'selected' : '' ?>>Prix décroissant</option>
                    <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Mieux notés</option>
                </select>
            </div>

            <div style="display:flex; gap:.6rem;">
                <button type="submit" class="btn-primary" style="height:42px; padding:0 1.2rem; font-size:.88rem;">Filtrer</button>
                <a href="hotels.php" style="height:42px; padding:0 1rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text-light); font-size:.88rem; text-decoration:none; transition:var(--transition);">✕</a>
            </div>

        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <div class="card-header">
            <h3>Liste des hôtels</h3>
            <span style="font-size:.85rem; color:var(--text-light);">
                <?= $total ?> résultat<?= $total > 1 ? 's' : '' ?>
            </span>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:70px;">Photo</th>
                        <th>Nom</th>
                        <th>Localisation</th>
                        <th>Type</th>
                        <th>Prix / nuit</th>
                        <th>Note</th>
                        <th>Étoiles</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hotels)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-light);">
                            <div style="font-size:2rem; margin-bottom:.5rem;">🏨</div>
                            Aucun hôtel trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($hotels as $hotel): ?>
                    <tr>
                        <td style="color:var(--text-light); font-size:.82rem;">#<?= $hotel['id'] ?></td>
                        <td>
                            <img
                                src="../../uploads/hotels/<?= htmlspecialchars($hotel['image'] ?? '') ?>"
                                onerror="this.src='../../assets/images/default.jpg'"
                                alt="<?= htmlspecialchars($hotel['name']) ?>"
                                style="width:52px; height:52px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.12);"
                            >
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text); font-size:.9rem;"><?= htmlspecialchars($hotel['name']) ?></div>
                        </td>
                        <td style="color:var(--text-light); font-size:.85rem;">
                            📍 <?= htmlspecialchars($hotel['location']) ?>
                        </td>
                        <td>
                            <?php if (!empty($hotel['type'])): ?>
                            <span style="font-size:.75rem; padding:.25rem .7rem; border-radius:50px; background:var(--primary-light); color:var(--primary); font-weight:500;">
                                <?= htmlspecialchars(ucfirst($hotel['type'])) ?>
                            </span>
                            <?php else: ?>
                            <span style="color:var(--text-light); font-size:.82rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700; color:var(--primary);">
                            <?= number_format($hotel['price'], 0, ',', ' ') ?> MAD
                        </td>
                        <td>
                            <span style="display:inline-flex; align-items:center; gap:.3rem; font-weight:600; font-size:.88rem; color:#f59e0b;">
                                ⭐ <?= number_format($hotel['rating'], 1) ?>
                            </span>
                        </td>
                        <td style="color:var(--text-light); font-size:.88rem;">
                            <?= !empty($hotel['stars']) ? str_repeat('★', $hotel['stars']) : '—' ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:.4rem; align-items:center;">
                                <a
                                    href="../../hotel-details.php?id=<?= $hotel['id'] ?>"
                                    target="_blank"
                                    title="Voir"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:var(--primary-light); color:var(--primary); font-size:.9rem; text-decoration:none; transition:var(--transition);"
                                >👁️</a>
                                <a
                                    href="edit-hotel.php?id=<?= $hotel['id'] ?>"
                                    title="Modifier"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(245,158,11,0.1); color:#f59e0b; font-size:.9rem; text-decoration:none; transition:var(--transition);"
                                >✏️</a>
                                <a
                                    href="hotels.php?delete=<?= $hotel['id'] ?>"
                                    title="Supprimer"
                                    onclick="return confirm('Supprimer cet hôtel ?')"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(239,68,68,0.1); color:#ef4444; font-size:.9rem; text-decoration:none; transition:var(--transition);"
                                >🗑️</a>
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
        <?php
        $query_params = $_GET;
        unset($query_params['page']);
        $base_query = http_build_query($query_params);
        $base_url   = 'hotels.php?' . ($base_query ? $base_query . '&' : '');
        ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 0 0; border-top:1px solid rgba(14,165,233,0.08); margin-top:1rem;">
            <span style="font-size:.82rem; color:var(--text-light);">
                Page <?= $page ?> / <?= $total_pages ?>
            </span>
            <div style="display:flex; gap:.4rem;">
                <?php if ($page > 1): ?>
                <a href="<?= $base_url ?>page=<?= $page - 1 ?>" style="height:34px; padding:0 .9rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text); font-size:.85rem; text-decoration:none;">← Préc.</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end   = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="<?= $base_url ?>page=<?= $i ?>" style="height:34px; width:34px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); border:1px solid <?= $i === $page ? 'var(--primary)' : 'rgba(14,165,233,0.15)' ?>; color:<?= $i === $page ? '#fff' : 'var(--text)' ?>; background:<?= $i === $page ? 'var(--primary)' : 'transparent' ?>; font-size:.85rem; text-decoration:none; font-weight:<?= $i === $page ? '600' : '400' ?>;"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="<?= $base_url ?>page=<?= $page + 1 ?>" style="height:34px; padding:0 .9rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text); font-size:.85rem; text-decoration:none;">Suiv. →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /admin-body -->
</main>
</div><!-- /admin-shell -->

<script src="../../assets/js/main.js"></script>
</body>
</html>