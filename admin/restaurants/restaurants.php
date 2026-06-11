<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort   = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'id_desc';
$filter_cuisine     = isset($_GET['cuisine'])     ? trim($_GET['cuisine'])     : '';
$filter_price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : '';

$where = "WHERE 1=1";
if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (name LIKE $s OR location LIKE $s OR cuisine LIKE $s)";
}
if ($filter_cuisine !== '') {
    $c = $pdo->quote($filter_cuisine);
    $where .= " AND cuisine = $c";
}
if ($filter_price_range !== '') {
    $p = $pdo->quote($filter_price_range);
    $where .= " AND price_range = $p";
}

$order_map = [
    'id_desc'    => 'id DESC',
    'id_asc'     => 'id ASC',
    'name_asc'   => 'name ASC',
    'rating_desc'=> 'rating DESC',
];
$order = $order_map[$sort] ?? 'id DESC';

$per_page    = 10;
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $per_page;
$total       = $pdo->query("SELECT COUNT(*) FROM restaurants $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$restaurants = $pdo->query("SELECT * FROM restaurants $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();

$price_range_labels = [
    'cheap'     => ['label' => '€ Pas cher',   'color' => '#10b981', 'bg' => 'rgba(16,185,129,.1)'],
    'moderate'  => ['label' => '€€ Modéré',    'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.1)'],
    'expensive' => ['label' => '€€€ Luxe',     'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.1)'],
];

$cuisines = $pdo->query("SELECT DISTINCT cuisine FROM restaurants WHERE cuisine IS NOT NULL ORDER BY cuisine")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
<main class="admin-main">
<div class="admin-topbar">
    <div class="topbar-left"><div>
        <div class="topbar-title">Restaurants</div>
        <div class="topbar-breadcrumb">Home <span>/</span> Contenu <span>/</span> Restaurants</div>
    </div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>
<div class="admin-body">

    <div class="page-header">
        <div><h1>🍽️ Gestion des Restaurants</h1><p><?= $total ?> restaurant<?= $total>1?'s':'' ?> au total</p></div>
        <a href="add-restaurant.php" class="btn-primary">+ Ajouter un restaurant</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Restaurant supprimé.</div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Restaurant enregistré.</div><?php endif; ?>

    <!-- FILTERS -->
    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Rechercher</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:var(--text-light);">🔍</span>
                    <input type="text" name="search" placeholder="Nom, cuisine, localisation..."
                           value="<?= htmlspecialchars($search) ?>"
                           style="width:100%; height:42px; padding:0 1rem 0 2.5rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; box-sizing:border-box;">
                </div>
            </div>
            <div>
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Cuisine</label>
                <select name="cuisine" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="">Toutes</option>
                    <?php foreach ($cuisines as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $filter_cuisine === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Gamme de prix</label>
                <select name="price_range" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="">Toutes</option>
                    <option value="cheap"     <?= $filter_price_range === 'cheap'     ? 'selected' : '' ?>>€ Pas cher</option>
                    <option value="moderate"  <?= $filter_price_range === 'moderate'  ? 'selected' : '' ?>>€€ Modéré</option>
                    <option value="expensive" <?= $filter_price_range === 'expensive' ? 'selected' : '' ?>>€€€ Luxe</option>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:.4rem;">Trier par</label>
                <select name="sort" style="height:42px; padding:0 1rem; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); background:var(--bg); color:var(--text); font-size:.88rem; outline:none; cursor:pointer;">
                    <option value="id_desc"    <?= $sort==='id_desc'    ?'selected':''?>>Plus récents</option>
                    <option value="name_asc"   <?= $sort==='name_asc'   ?'selected':''?>>Nom A→Z</option>
                    <option value="rating_desc"<?= $sort==='rating_desc'?'selected':''?>>Mieux notés</option>
                </select>
            </div>
            <div style="display:flex; gap:.6rem;">
                <button type="submit" class="btn-primary" style="height:42px; padding:0 1.2rem; font-size:.88rem;">Filtrer</button>
                <a href="restaurants.php" style="height:42px; padding:0 1rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15); color:var(--text-light); font-size:.88rem; text-decoration:none;">✕</a>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <div class="card-header">
            <h3>Liste des restaurants</h3>
            <span style="font-size:.85rem; color:var(--text-light);"><?= $total ?> résultat<?= $total>1?'s':'' ?></span>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:70px;">Photo</th>
                        <th>Nom</th>
                        <th>Cuisine</th>
                        <th>Localisation</th>
                        <th>Prix</th>
                        <th>Note</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($restaurants)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:3rem; color:var(--text-light);"><div style="font-size:2rem; margin-bottom:.5rem;">🍽️</div>Aucun restaurant trouvé</td></tr>
                <?php else: ?>
                    <?php foreach ($restaurants as $r):
                        $pr = $price_range_labels[$r['price_range']] ?? ['label' => $r['price_range'], 'color' => 'var(--text-light)', 'bg' => 'transparent'];
                    ?>
                    <tr>
                        <td style="color:var(--text-light); font-size:.82rem;">#<?= $r['id'] ?></td>
                        <td>
                            <img src="../../uploads/restaurants/<?= htmlspecialchars($r['image'] ?? '') ?>"
                                 onerror="this.src='../../assets/images/default.jpg'"
                                 style="width:52px; height:52px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.12);">
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text); font-size:.9rem;"><?= htmlspecialchars($r['name']) ?></div>
                            <?php if (!empty($r['description'])): ?>
                                <div style="font-size:.78rem; color:var(--text-light); margin-top:.2rem; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($r['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-light); font-size:.85rem;"><?= htmlspecialchars($r['cuisine'] ?? '—') ?></td>
                        <td style="color:var(--text-light); font-size:.85rem;">📍 <?= htmlspecialchars($r['location'] ?? '—') ?></td>
                        <td>
                            <span style="padding:.2rem .7rem; border-radius:50px; font-size:.78rem; font-weight:600; background:<?= $pr['bg'] ?>; color:<?= $pr['color'] ?>;">
                                <?= $pr['label'] ?>
                            </span>
                        </td>
                        <td><span style="font-weight:600; font-size:.88rem; color:#f59e0b;">⭐ <?= number_format($r['rating'], 1) ?></span></td>
                        <td>
                            <div style="display:flex; gap:.4rem;">
                                <a href="edit-restaurant.php?id=<?= $r['id'] ?>"
                                   style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(245,158,11,0.1); color:#f59e0b; text-decoration:none;" title="Modifier">✏️</a>
                                <a href="delete-restaurant.php?id=<?= $r['id'] ?>"
                                   onclick="return confirm('Supprimer ce restaurant ?')"
                                   style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); background:rgba(239,68,68,0.1); color:#ef4444; text-decoration:none;" title="Supprimer">🗑️</a>
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
            $bu = 'restaurants.php?' . (http_build_query($qp) ? http_build_query($qp).'&' : '');
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