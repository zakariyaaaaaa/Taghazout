<?php
session_start();
require_once '../../includes/config.php';

// ─── Admin check ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ─── Filters ──────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$type_filter = $_GET['type'] ?? '';
$rating_filter = $_GET['rating'] ?? '';

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(u.name LIKE ? OR r.comment LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type_filter !== '') {
    $where[]  = "r.type = ?";
    $params[] = $type_filter;
}
if ($rating_filter !== '') {
    $where[]  = "r.rating = ?";
    $params[] = (int)$rating_filter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Stats ────────────────────────────────────────────────
$stats = [
    'total'   => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'avg'     => $pdo->query("SELECT ROUND(AVG(rating),1) FROM reviews")->fetchColumn(),
    'five'    => $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating = 5")->fetchColumn(),
    'one'     => $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating = 1")->fetchColumn(),
];

// ─── Fetch reviews ────────────────────────────────────────
$sql = "
    SELECT r.*,
           u.name  AS user_name,
           u.email AS user_email,
           CASE r.type
               WHEN 'hotel'      THEN h.name
               WHEN 'surf'       THEN sc.title
               WHEN 'activity'   THEN a.name
               WHEN 'restaurant' THEN rs.name
               ELSE NULL
           END AS item_name
    FROM reviews r
    LEFT JOIN users        u  ON r.user_id     = u.id
    LEFT JOIN hotels       h  ON r.type = 'hotel'      AND r.reference_id = h.id
    LEFT JOIN surf_courses sc ON r.type = 'surf'       AND r.reference_id = sc.id
    LEFT JOIN activities   a  ON r.type = 'activity'   AND r.reference_id = a.id
    LEFT JOIN restaurants  rs ON r.type = 'restaurant' AND r.reference_id = rs.id
    $whereSQL
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">

<?php
$_SERVER['PHP_SELF'] = '/admin/reviews/reviews.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<main class="admin-main">

    <!-- TOPBAR -->
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Avis</div>
                <div class="topbar-breadcrumb">Home <span>/</span> Avis</div>
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
                <h1>Avis clients</h1>
                <p>Gérez et modérez les avis laissés par les utilisateurs</p>
            </div>
            <span style="font-size:0.85rem; color:var(--text-light);"><?= date('d/m/Y') ?></span>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
            <div class="stat-card teal">
                <div class="stat-icon" style="background:rgba(14,165,233,0.1); color:var(--ocean-teal);">⭐</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total avis</div>
                </div>
            </div>
            <div class="stat-card sand">
                <div class="stat-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;">📊</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['avg'] ?> / 5</div>
                    <div class="stat-label">Note moyenne</div>
                </div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:#10b981;">🌟</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['five']) ?></div>
                    <div class="stat-label">Avis 5 étoiles</div>
                </div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;">👎</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['one']) ?></div>
                    <div class="stat-label">Avis 1 étoile</div>
                </div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="card" style="margin-top:1.5rem;">
            <form method="GET" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-light); margin-bottom:0.4rem;">Recherche</label>
                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Nom client, commentaire..."
                        style="width:100%; padding:0.6rem 0.9rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:inherit; font-size:0.88rem; background:var(--bg); color:var(--text);"
                    >
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-light); margin-bottom:0.4rem;">Type</label>
                    <select name="type" style="padding:0.6rem 0.9rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:inherit; font-size:0.88rem; background:var(--bg); color:var(--text);">
                        <option value="">Tous</option>
                        <option value="hotel"    <?= $type_filter === 'hotel'    ? 'selected' : '' ?>>🏨 Hôtels</option>
                        <option value="surf"     <?= $type_filter === 'surf'     ? 'selected' : '' ?>>🏄 Surf</option>
                        <option value="activity"   <?= $type_filter === 'activity'   ? 'selected' : '' ?>>🎯 Activités</option>
                        <option value="restaurant" <?= $type_filter === 'restaurant' ? 'selected' : '' ?>>🍽️ Restaurants</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-light); margin-bottom:0.4rem;">Note</label>
                    <select name="rating" style="padding:0.6rem 0.9rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:inherit; font-size:0.88rem; background:var(--bg); color:var(--text);">
                        <option value="">Toutes</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= $rating_filter == $i ? 'selected' : '' ?>><?= str_repeat('⭐', $i) ?> (<?= $i ?>)</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="display:flex; gap:0.6rem;">
                    <button type="submit" class="btn-primary" style="padding:0.6rem 1.2rem; font-size:0.88rem;">
                        🔍 Filtrer
                    </button>
                    <?php if ($search || $type_filter || $rating_filter): ?>
                    <a href="reviews.php" style="padding:0.6rem 1rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-size:0.88rem; color:var(--text-light); text-decoration:none;">
                        ✕ Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- REVIEWS TABLE -->
        <div class="card" style="margin-top:1.5rem;">
            <div class="card-header">
                <h3>⭐ Liste des avis <span style="font-size:0.82rem; color:var(--text-light); font-weight:400;">(<?= count($reviews) ?> résultats)</span></h3>
            </div>

            <?php if (empty($reviews)): ?>
            <div style="text-align:center; padding:3rem; color:var(--text-light);">
                <div style="font-size:2.5rem; margin-bottom:1rem;">🔍</div>
                <p>Aucun avis trouvé.</p>
            </div>
            <?php else: ?>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Sur</th>
                            <th>Type</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:600; font-size:0.88rem;"><?= htmlspecialchars($r['user_name'] ?? 'N/A') ?></div>
                                <div style="font-size:0.75rem; color:var(--text-light);"><?= htmlspecialchars($r['user_email'] ?? '') ?></div>
                            </td>
                            <td style="font-size:0.85rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= htmlspecialchars($r['item_name'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'hotel'    => ['🏨 Hôtel',    'var(--primary-light)',      'var(--primary)'],
                                    'surf'     => ['🏄 Surf',     'rgba(16,185,129,0.1)',      '#10b981'],
                                    'activity'   => ['🎯 Activité',   'rgba(139,92,246,0.1)', '#8b5cf6'],
                    'restaurant' => ['🍽️ Restaurant', 'rgba(245,158,11,0.1)', '#f59e0b'],
                                ];
                                $tl = $typeLabels[$r['type']] ?? [$r['type'], 'var(--bg)', 'var(--text)'];
                                ?>
                                <span style="font-size:0.75rem; padding:0.2rem 0.6rem; border-radius:50px; background:<?= $tl[1] ?>; color:<?= $tl[2] ?>; font-weight:600;">
                                    <?= $tl[0] ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $rating = (int)$r['rating'];
                                $color  = $rating >= 4 ? '#10b981' : ($rating == 3 ? '#f59e0b' : '#ef4444');
                                ?>
                                <span style="font-weight:700; color:<?= $color ?>; font-size:0.9rem;">
                                    <?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?>
                                </span>
                                <span style="font-size:0.75rem; color:var(--text-light);"> (<?= $rating ?>)</span>
                            </td>
                            <td style="max-width:220px;">
                                <div style="font-size:0.83rem; color:var(--text); overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                                    <?= htmlspecialchars($r['comment'] ?? '—') ?>
                                </div>
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-light); white-space:nowrap;">
                                <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                            </td>
                            <td>
                                <button
                                    onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['user_name'] ?? 'cet avis')) ?>')"
                                    style="font-size:0.78rem; padding:0.3rem 0.7rem; border:1.5px solid rgba(239,68,68,0.3); border-radius:var(--radius-sm); background:rgba(239,68,68,0.05); color:#ef4444; cursor:pointer; font-weight:600; transition:all 0.2s;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.15)'"
                                    onmouseout="this.style.background='rgba(239,68,68,0.05)'"
                                >
                                    🗑️ Supprimer
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /admin-body -->
</main>
</div><!-- /admin-shell -->

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--surface); border-radius:var(--radius); padding:2rem; max-width:400px; width:90%; box-shadow:var(--shadow-lg);">
        <div style="font-size:2rem; text-align:center; margin-bottom:1rem;">🗑️</div>
        <h3 style="text-align:center; margin-bottom:0.5rem;">Supprimer l'avis</h3>
        <p id="deleteModalText" style="text-align:center; color:var(--text-light); font-size:0.9rem; margin-bottom:1.5rem;"></p>
        <div style="display:flex; gap:0.8rem; justify-content:center;">
            <button onclick="closeModal()" style="padding:0.6rem 1.4rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); background:transparent; color:var(--text); cursor:pointer; font-family:inherit; font-size:0.88rem;">
                Annuler
            </button>
            <form id="deleteForm" method="POST" action="delete-review.php" style="margin:0;">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" style="padding:0.6rem 1.4rem; border:none; border-radius:var(--radius-sm); background:#ef4444; color:white; cursor:pointer; font-family:inherit; font-size:0.88rem; font-weight:600;">
                    Confirmer
                </button>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalText').textContent = `Voulez-vous vraiment supprimer l'avis de "${name}" ? Cette action est irréversible.`;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Notifications badge
(function(){
    function pollUnread() {
        fetch('../../user/messages/get-unread.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const link = document.querySelector('.sb-nav a[href*="messages"]');
                if (!link) return;
                let badge = link.querySelector('.nav-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'nav-badge';
                    link.appendChild(badge);
                }
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'inline-flex';
                    document.title = `(${data.count}) ` + document.title.replace(/^\(\d+\) /,'');
                } else {
                    badge.style.display = 'none';
                    document.title = document.title.replace(/^\(\d+\) /,'');
                }
            }).catch(()=>{});
    }
    pollUnread();
    setInterval(pollUnread, 5000);
})();
</script>
</body>
</html>