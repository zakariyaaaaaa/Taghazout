<?php
session_start();
require_once('../../includes/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort   = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'id_desc';
$filter_level = isset($_GET['level']) ? trim($_GET['level']) : '';

$where = "WHERE 1=1";
if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (title LIKE $s OR description LIKE $s)";
}
if ($filter_level !== '') {
    $l = $pdo->quote($filter_level);
    $where .= " AND level = $l";
}

$order_map = [
    'id_desc'    => 'id DESC',
    'id_asc'     => 'id ASC',
    'name_asc'   => 'title ASC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
];
$order = $order_map[$sort] ?? 'id DESC';

$per_page    = 10;
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $per_page;
$total       = $pdo->query("SELECT COUNT(*) FROM surf_courses $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$courses     = $pdo->query("SELECT * FROM surf_courses $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();

$level_map = [
    'beginner'     => ['label' => '🟢 Débutant',      'color' => '#10b981', 'bg' => 'rgba(16,185,129,.1)'],
    'intermediate' => ['label' => '🟡 Intermédiaire', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.1)'],
    'advanced'     => ['label' => '🔴 Avancé',        'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.1)'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours de Surf — Admin Taghazout</title>
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
        <div class="topbar-title">Cours de Surf</div>
        <div class="topbar-breadcrumb">Home <span>/</span> Contenu <span>/</span> Surf Courses</div>
    </div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>
<div class="admin-body">

    <div class="page-header">
        <div><h1>🏄 Gestion des Cours de Surf</h1><p><?= $total ?> cours au total</p></div>
        <a href="add-course.php" class="btn-primary">+ Ajouter un cours</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Cours supprimé.</div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="alert-success" style="margin-bottom:1.25rem;">✅ Cours enregistré.</div><?php endif; ?>

    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.75rem;">
        <?php
        $all_courses = $pdo->query("SELECT * FROM surf_courses")->fetchAll();
        $stat_cards = [
            ['icon'=>'🏄','value'=>count($all_courses),'label'=>'Total cours','color'=>'var(--primary)'],
            ['icon'=>'👥','value'=>array_sum(array_column($all_courses,'max_students')),'label'=>'Places totales','color'=>'#10b981'],
            ['icon'=>'💰','value'=>number_format(array_sum(array_column($all_courses,'price')),0,',',' ').' MAD','label'=>'Valeur totale','color'=>'#f59e0b'],
        ];
        foreach ($stat_cards as $sc):
        ?>
        <div style="background:var(--card-bg); border-radius:var(--radius); padding:1.4rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,.06); display:flex; align-items:center; gap:1rem;">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;"><?= $sc['icon'] ?></div>
            <div>
                <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:700;color:<?= $sc['color'] ?>;line-height:1;"><?= $sc['value'] ?></div>
                <div style="font-size:.8rem;color:var(--text-light);margin-top:.2rem;"><?= $sc['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="font-size:.75rem;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:.4rem;">Rechercher</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-light);">🔍</span>
                    <input type="text" name="search" placeholder="Titre, description..."
                           value="<?= htmlspecialchars($search) ?>"
                           style="width:100%;height:42px;padding:0 1rem 0 2.5rem;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);background:var(--bg);color:var(--text);font-size:.88rem;outline:none;box-sizing:border-box;">
                </div>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:.4rem;">Niveau</label>
                <select name="level" style="height:42px;padding:0 1rem;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);background:var(--bg);color:var(--text);font-size:.88rem;outline:none;cursor:pointer;">
                    <option value="">Tous</option>
                    <option value="beginner"     <?= $filter_level==='beginner'     ?'selected':''?>>🟢 Débutant</option>
                    <option value="intermediate" <?= $filter_level==='intermediate' ?'selected':''?>>🟡 Intermédiaire</option>
                    <option value="advanced"     <?= $filter_level==='advanced'     ?'selected':''?>>🔴 Avancé</option>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:.4rem;">Trier par</label>
                <select name="sort" style="height:42px;padding:0 1rem;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);background:var(--bg);color:var(--text);font-size:.88rem;outline:none;cursor:pointer;">
                    <option value="id_desc"   <?= $sort==='id_desc'  ?'selected':''?>>Plus récents</option>
                    <option value="name_asc"  <?= $sort==='name_asc' ?'selected':''?>>Titre A→Z</option>
                    <option value="price_asc" <?= $sort==='price_asc'?'selected':''?>>Prix croissant</option>
                    <option value="price_desc"<?= $sort==='price_desc'?'selected':''?>>Prix décroissant</option>
                </select>
            </div>
            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-primary" style="height:42px;padding:0 1.2rem;font-size:.88rem;">Filtrer</button>
                <a href="courses.php" style="height:42px;padding:0 1rem;display:flex;align-items:center;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);color:var(--text-light);font-size:.88rem;text-decoration:none;">✕</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header">
            <h3>Liste des cours</h3>
            <span style="font-size:.85rem;color:var(--text-light);"><?= $total ?> résultat<?= $total>1?'s':'' ?></span>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:70px;">Photo</th>
                        <th>Titre</th>
                        <th>Niveau</th>
                        <th>Prix</th>
                        <th>Durée</th>
                        <th>Max étudiants</th>
                        <th>Date</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($courses)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-light);"><div style="font-size:2rem;margin-bottom:.5rem;">🏄</div>Aucun cours trouvé</td></tr>
                <?php else: ?>
                    <?php foreach ($courses as $i => $c):
                        $lv = $level_map[$c['level']] ?? ['label'=>$c['level'],'color'=>'var(--text-light)','bg'=>'transparent'];
                    ?>
                    <tr>
                        <td style="color:var(--text-light);font-size:.82rem;">#<?= $c['id'] ?></td>
                        <td>
                            <img src="../../uploads/surf/<?= htmlspecialchars($c['image'] ?? '') ?>"
                                 onerror="this.src='../../assets/images/default.jpg'"
                                 style="width:52px;height:52px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.12);">
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--text);font-size:.9rem;"><?= htmlspecialchars($c['title']) ?></div>
                            <?php if (!empty($c['description'])): ?>
                            <div style="font-size:.78rem;color:var(--text-light);margin-top:.2rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($c['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="padding:.2rem .7rem;border-radius:50px;font-size:.78rem;font-weight:600;background:<?= $lv['bg'] ?>;color:<?= $lv['color'] ?>;">
                                <?= $lv['label'] ?>
                            </span>
                        </td>
                        <td style="font-weight:700;color:var(--primary);"><?= number_format($c['price'],0,',',' ') ?> MAD</td>
                        <td style="color:var(--text-light);font-size:.85rem;">⏱ <?= htmlspecialchars($c['duration'] ?? '—') ?></td>
                        <td><span style="font-size:.85rem;color:var(--text-light);">👥 <?= intval($c['max_students']) ?></span></td>
                        <td style="color:var(--text-light);font-size:.82rem;"><?= date('d/m/Y', strtotime($c['created_at'] ?? 'now')) ?></td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="edit-course.php?id=<?= $c['id'] ?>"
                                   style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-sm);background:rgba(245,158,11,0.1);color:#f59e0b;text-decoration:none;" title="Modifier">✏️</a>
                                <a href="delete-course.php?id=<?= $c['id'] ?>"
                                   onclick="return confirm('Supprimer ce cours ?')"
                                   style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-sm);background:rgba(239,68,68,0.1);color:#ef4444;text-decoration:none;" title="Supprimer">🗑️</a>
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
            $bu = 'courses.php?' . (http_build_query($qp) ? http_build_query($qp).'&' : '');
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 0 0;border-top:1px solid rgba(14,165,233,0.08);margin-top:1rem;">
            <span style="font-size:.82rem;color:var(--text-light);">Page <?= $page ?> / <?= $total_pages ?></span>
            <div style="display:flex;gap:.4rem;">
                <?php if ($page>1): ?><a href="<?= $bu ?>page=<?= $page-1 ?>" style="height:34px;padding:0 .9rem;display:flex;align-items:center;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);color:var(--text);font-size:.85rem;text-decoration:none;">← Préc.</a><?php endif; ?>
                <?php for ($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
                <a href="<?= $bu ?>page=<?= $i ?>" style="height:34px;width:34px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-sm);border:1px solid <?= $i===$page?'var(--primary)':'rgba(14,165,233,0.15)' ?>;color:<?= $i===$page?'#fff':'var(--text)' ?>;background:<?= $i===$page?'var(--primary)':'transparent' ?>;font-size:.85rem;text-decoration:none;"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page<$total_pages): ?><a href="<?= $bu ?>page=<?= $page+1 ?>" style="height:34px;padding:0 .9rem;display:flex;align-items:center;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.15);color:var(--text);font-size:.85rem;text-decoration:none;">Suiv. →</a><?php endif; ?>
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