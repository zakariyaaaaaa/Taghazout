<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort   = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'id_desc';

$where = "WHERE 1=1";
if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (name LIKE $s OR location LIKE $s)";
}

$order_map = [
    'id_desc'     => 'id DESC',   'id_asc'   => 'id ASC',
    'name_asc'    => 'name ASC',  'price_asc' => 'price ASC',
    'price_desc'  => 'price DESC','rating_desc'=> 'rating DESC',
];
$order = $order_map[$sort] ?? 'id DESC';

$per_page    = 10;
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $per_page;
$total       = $pdo->query("SELECT COUNT(*) FROM activities $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$activities  = $pdo->query("SELECT * FROM activities $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
<aside class="admin-sidebar">
    <div class="sb-logo"><div class="sb-logo-mark">🏄</div><div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div></div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="activities.php" class="active"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="../payments/payments.php"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
    </nav>
    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info"><strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong><span>Administrator</span></div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>
<main class="admin-main">
<div class="admin-topbar">
    <div class="topbar-left"><div><div class="topbar-title">Activités</div><div class="topbar-breadcrumb">Home <span>/</span> Contenu <span>/</span> Activités</div></div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>
<div class="admin-body">

    <div class="page-header">
        <div><h1>🎯 Gestion des Activités</h1><p><?= $total ?> activité<?= $total>1?'s':'' ?> au total</p></div>
        <a href="add-activity.php" class="btn-primary">+ Ajouter une activité</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Activité supprimée.</div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Activité enregistrée.</div><?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Rechercher</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:var(--text-light);">🔍</span>
                    <input type="text" name="search" placeholder="Nom, localisation..." value="<?= htmlspecialchars($search) ?>"
                        style="width:100%; height:42px; padding:0 1rem 0 2.5rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none;">
                </div>
            </div>
            <div style="min-width:160px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Trier par</label>
                <select name="sort" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="id_desc"     <?= $sort==='id_desc'    ?'selected':''?>>Plus récentes</option>
                    <option value="name_asc"    <?= $sort==='name_asc'   ?'selected':''?>>Nom A→Z</option>
                    <option value="price_asc"   <?= $sort==='price_asc'  ?'selected':''?>>Prix croissant</option>
                    <option value="price_desc"  <?= $sort==='price_desc' ?'selected':''?>>Prix décroissant</option>
                    <option value="rating_desc" <?= $sort==='rating_desc'?'selected':''?>>Mieux notées</option>
                </select>
            </div>
            <div style="display:flex; gap:.6rem;">
                <button type="submit" class="btn-primary" style="height:42px; padding:0 1.2rem; font-size:.88rem;">Filtrer</button>
                <a href="activities.php" style="height:42px; padding:0 1rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text-light); font-size:.88rem; text-decoration:none;">✕</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Liste des activités</h3>
            <span style="font-size:.85rem; color:var(--text-light);"><?= $total ?> résultat<?= $total>1?'s':'' ?></span>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:70px;">Photo</th>
                        <th>Nom</th>
                        <th>Localisation</th>
                        <th>Durée</th>
                        <th>Prix / pers.</th>
                        <th>Note</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($activities)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:3rem; color:var(--text-light);"><div style="font-size:2rem; margin-bottom:.5rem;">🎯</div>Aucune activité trouvée</td></tr>
                <?php else: ?>
                    <?php foreach ($activities as $a): ?>
                    <tr>
                        <td style="color:var(--text-light); font-size:.82rem;">#<?= $a['id'] ?></td>
                        <td>
                            <img src="../../assets/images/activities/<?= htmlspecialchars($a['image'] ?? '') ?>"
                                onerror="this.src='../../assets/images/default.jpg'"
                                style="width:52px; height:52px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.12);">
                        </td>
                        <td><div style="font-weight:600; color:var(--text); font-size:.9rem;"><?= htmlspecialchars($a['name']) ?></div></td>
                        <td style="color:var(--text-light); font-size:.85rem;">📍 <?= htmlspecialchars($a['location']) ?></td>
                        <td style="color:var(--text-light); font-size:.85rem;">⏱ <?= htmlspecialchars($a['duration'] ?? '—') ?></td>
                        <td style="font-weight:700; color:var(--primary);"><?= number_format($a['price'], 0, ',', ' ') ?> MAD</td>
                        <td><span style="font-weight:600; font-size:.88rem; color:#f59e0b;">⭐ <?= number_format($a['rating'], 1) ?></span></td>
                        <td>
                            <div style="display:flex; gap:.4rem;">
                                <a href="../../activity-details.php?id=<?= $a['id'] ?>" target="_blank"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:var(--primary-light); color:var(--primary); text-decoration:none;">👁️</a>
                                <a href="edit-activity.php?id=<?= $a['id'] ?>"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(245,158,11,0.1); color:#f59e0b; text-decoration:none;">✏️</a>
                                <a href="delete-activity.php?id=<?= $a['id'] ?>" onclick="return confirm('Supprimer cette activité ?')"
                                    style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(239,68,68,0.1); color:#ef4444; text-decoration:none;">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1):
            $qp = $_GET; unset($qp['page']);
            $bu = 'activities.php?' . (http_build_query($qp) ? http_build_query($qp).'&' : '');
        ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 0 0; border-top:1px solid rgba(14,165,233,0.08); margin-top:1rem;">
            <span style="font-size:.82rem; color:var(--text-light);">Page <?= $page ?> / <?= $total_pages ?></span>
            <div style="display:flex; gap:.4rem;">
                <?php if ($page > 1): ?><a href="<?= $bu ?>page=<?= $page-1 ?>" style="height:34px; padding:0 .9rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text); font-size:.85rem; text-decoration:none;">← Préc.</a><?php endif; ?>
                <?php for ($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
                <a href="<?= $bu ?>page=<?= $i ?>" style="height:34px; width:34px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); border:1px solid <?= $i===$page?'var(--primary)':'rgba(14,165,233,0.15)' ?>; color:<?= $i===$page?'#fff':'var(--text)' ?>; background:<?= $i===$page?'var(--primary)':'transparent' ?>; font-size:.85rem; text-decoration:none;"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?><a href="<?= $bu ?>page=<?= $page+1 ?>" style="height:34px; padding:0 .9rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text); font-size:.85rem; text-decoration:none;">Suiv. →</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>