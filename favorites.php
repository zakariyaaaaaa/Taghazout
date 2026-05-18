<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ── Active tab ────────────────────────────────────────────────
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'all';
$allowed_tabs = ['all', 'hotel', 'activity', 'surf', 'restaurant'];
if (!in_array($tab, $allowed_tabs, true)) $tab = 'all';

// ── Fetch favorites ───────────────────────────────────────────
// Hotels
$hotels = $pdo->prepare("
    SELECT h.*, 'hotel' AS item_type, f.created_at AS fav_date
    FROM favorites f
    JOIN hotels h ON h.id = f.reference_id
    WHERE f.user_id = ? AND f.type = 'hotel'
    ORDER BY f.created_at DESC
");
$hotels->execute([$user_id]);
$hotels = $hotels->fetchAll();

// Activities
$activities = $pdo->prepare("
    SELECT a.*, 'activity' AS item_type, f.created_at AS fav_date
    FROM favorites f
    JOIN activities a ON a.id = f.reference_id
    WHERE f.user_id = ? AND f.type = 'activity'
    ORDER BY f.created_at DESC
");
$activities->execute([$user_id]);
$activities = $activities->fetchAll();

// Surf courses
$surfs = $pdo->prepare("
    SELECT s.*, 'surf' AS item_type, f.created_at AS fav_date
    FROM favorites f
    JOIN surf_courses s ON s.id = f.reference_id
    WHERE f.user_id = ? AND f.type = 'surf'
    ORDER BY f.created_at DESC
");
$surfs->execute([$user_id]);
$surfs = $surfs->fetchAll();

// ── Merge for "all" tab ───────────────────────────────────────
$all = array_merge($hotels, $activities, $surfs);
usort($all, fn($a, $b) => strtotime($b['fav_date']) - strtotime($a['fav_date']));

// ── Tab counts ────────────────────────────────────────────────
$counts = [
    'all'      => count($all),
    'hotel'    => count($hotels),
    'activity' => count($activities),
    'surf'     => count($surfs),
];

// ── Items to display ──────────────────────────────────────────
$items = match($tab) {
    'hotel'    => $hotels,
    'activity' => $activities,
    'surf'     => $surfs,
    default    => $all,
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotels.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <style>
        /* ── Tabs ── */
        .fav-tabs {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: center;
            padding: 1.75rem 1.5rem 1rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .fav-tab {
            padding: .45rem 1.1rem;
            border-radius: 50px;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            background: var(--card-bg);
            color: var(--text-light);
            border: 1px solid rgba(14,165,233,0.12);
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: .35rem;
        }
        .fav-tab:hover,
        .fav-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .fav-tab-count {
            background: rgba(255,255,255,0.25);
            border-radius: 50px;
            padding: .05rem .45rem;
            font-size: .75rem;
        }
        .fav-tab:not(.active) .fav-tab-count {
            background: rgba(14,165,233,0.1);
            color: var(--primary);
        }

        /* ── Grid (reuse hotels-grid) ── */
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            max-width: 1200px;
            margin: 0 auto 4rem;
            padding: 0 1.5rem;
        }

        /* ── Type badge ── */
        .card-type-badge {
            position: absolute;
            top: .7rem;
            left: .7rem;
            background: rgba(0,0,0,.5);
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            padding: .2rem .6rem;
            border-radius: 50px;
            backdrop-filter: blur(4px);
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ── Remove btn ── */
        .btn-remove-fav {
            position: absolute;
            top: .7rem;
            right: .7rem;
            background: rgba(220,38,38,.85);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .2s, opacity .2s;
            opacity: 0;
        }
        .hotel-card:hover .btn-remove-fav { opacity: 1; }

        /* ── Empty state ── */
        .fav-empty {
            text-align: center;
            padding: 5rem 2rem;
            grid-column: 1/-1;
            color: var(--text-light);
        }
        .fav-empty .emoji { font-size: 3.5rem; display: block; margin-bottom: 1rem; }
        .fav-empty h3 { font-family: 'Syne', sans-serif; font-size: 1.2rem; margin-bottom: .5rem; color: var(--text-primary); }
        .fav-empty .explore-links { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem; }
        .fav-empty .explore-links a {
            padding: .5rem 1.2rem;
            border-radius: 50px;
            font-size: .85rem;
            font-weight: 600;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            transition: opacity .2s;
        }
        .fav-empty .explore-links a:hover { opacity: .85; }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">❤️ Taghazout Platform</p>
        <h1>Mes Favoris</h1>
        <p>Retrouvez tous vos lieux et expériences sauvegardés</p>
    </div>
</section>

<!-- TABS -->
<div class="fav-tabs">
    <?php
    $tab_defs = [
        'all'      => ['label' => '🌍 Tout',         'count' => $counts['all']],
        'hotel'    => ['label' => '🏨 Hôtels',        'count' => $counts['hotel']],
        'activity' => ['label' => '🏄 Activités',     'count' => $counts['activity']],
        'surf'     => ['label' => '🌊 Surf',           'count' => $counts['surf']],
    ];
    foreach ($tab_defs as $key => $def):
    ?>
        <a href="favorites.php?tab=<?= $key ?>"
           class="fav-tab <?= $tab === $key ? 'active' : '' ?>">
            <?= $def['label'] ?>
            <span class="fav-tab-count"><?= $def['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- RESULTS BAR -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= count($items) ?></strong> favori<?= count($items) > 1 ? 's' : '' ?>
    </p>
</div>

<!-- GRID -->
<div class="fav-grid">
    <?php if (empty($items)): ?>
        <div class="fav-empty">
            <span class="emoji">❤️</span>
            <h3>Aucun favori pour l'instant</h3>
            <p>Explorez et ajoutez vos lieux préférés !</p>
            <div class="explore-links">
                <a href="hotels.php">🏨 Hôtels</a>
                <a href="activities.php">🏄 Activités</a>
                <a href="surf-courses.php">🌊 Surf</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item):
            $type = $item['item_type'];
            $type_map = [
                'hotel'    => ['label' => '🏨 Hôtel',    'img_dir' => 'hotels',      'link' => 'hotel-details.php',    'name_key' => 'name'],
                'activity' => ['label' => '🏄 Activité', 'img_dir' => 'activities',  'link' => 'activity-details.php', 'name_key' => 'name'],
                'surf'     => ['label' => '🌊 Surf',      'img_dir' => 'surf',        'link' => 'course-details.php',   'name_key' => 'title'],
            ];
            $meta  = $type_map[$type] ?? $type_map['hotel'];
            $name  = htmlspecialchars($item[$meta['name_key']] ?? '');
            $img   = '../uploads/' . $meta['img_dir'] . '/' . htmlspecialchars($item['image'] ?? '');
            $price = isset($item['price']) ? number_format($item['price'], 0, ',', ' ') . ' MAD' : '';
            $sub   = '';
            if ($type === 'hotel')    $sub = '📍 ' . htmlspecialchars($item['location'] ?? '');
            if ($type === 'activity') $sub = '⏱️ ' . htmlspecialchars($item['duration'] ?? '');
            if ($type === 'surf')     $sub = '🏄 ' . ucfirst($item['level'] ?? '');
        ?>
        <div class="hotel-card" id="fav-card-<?= $type ?>-<?= $item['id'] ?>">
            <div class="card-img" style="position:relative">
                <img src="<?= $img ?>"
                     onerror="this.src='../assets/images/default.jpg'"
                     alt="<?= $name ?>"
                     loading="lazy">
                <span class="card-type-badge"><?= $meta['label'] ?></span>
                <?php if (!empty($item['rating'])): ?>
                    <span class="card-badge">⭐ <?= number_format($item['rating'], 1) ?></span>
                <?php endif; ?>
                <button
                    class="btn-remove-fav"
                    title="Retirer des favoris"
                    onclick="removeFav(this, <?= $item['id'] ?>, '<?= $type ?>')"
                >✕</button>
            </div>
            <div class="card-body">
                <h3><?= $name ?></h3>
                <?php if ($sub): ?><p class="location"><?= $sub ?></p><?php endif; ?>
                <div class="card-footer">
                    <?php if ($price): ?>
                        <div class="price"><?= $price ?><?= $type === 'hotel' ? '<span>/ nuit</span>' : '' ?></div>
                    <?php else: ?><div></div><?php endif; ?>
                    <a href="<?= $meta['link'] ?>?id=<?= $item['id'] ?>" class="btn-card">Voir →</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
function removeFav(btn, id, type) {
    fetch('/taghazout_platform/api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, type })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.added) {
            // animate out
            const card = document.getElementById('fav-card-' + type + '-' + id);
            if (card) {
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(.95)';
                setTimeout(() => card.remove(), 300);
            }
        }
    });
}
</script>
</body>
</html>