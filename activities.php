<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Pagination ───────────────────────────────────────────
$per_page = 9;
$page     = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

// ─── Filters ──────────────────────────────────────────────
$search     = isset($_GET['search'])    ? trim($_GET['search'])    : '';
$min_price  = isset($_GET['min_price']) ? (int)$_GET['min_price']  : 0;
$max_price  = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 9999;
$min_rating = isset($_GET['rating'])    ? (float)$_GET['rating']   : 0;
$sort       = isset($_GET['sort'])      ? trim($_GET['sort'])       : 'rating_desc';

// ─── Build WHERE ──────────────────────────────────────────
$where = "WHERE 1=1";

if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (name LIKE $s OR location LIKE $s)";
}
if ($min_price > 0) {
    $where .= " AND price >= " . (int)$min_price;
}
if ($max_price < 9999) {
    $where .= " AND price <= " . (int)$max_price;
}
if ($min_rating > 0) {
    $where .= " AND rating >= " . (float)$min_rating;
}

// ─── Sort ─────────────────────────────────────────────────
$order_map = [
    'rating_desc' => 'rating DESC',
    'price_asc'   => 'price ASC',
    'price_desc'  => 'price DESC',
    'name_asc'    => 'name ASC',
];
$order = $order_map[$sort] ?? 'rating DESC';

// ─── Count + Fetch ────────────────────────────────────────
$total       = $pdo->query("SELECT COUNT(*) FROM activities $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$activities  = $pdo->query("SELECT * FROM activities $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotels.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
        <video autoplay muted loop playsinline class="hero-video">
                    <source src="../assets/videos/Activites.mp4" type="video/mp4">
                </video>
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">🏄 Taghazout Platform</p>
        <h1>Nos Activités</h1>
        <p>Découvrez les meilleures activités à Taghazout et ses environs</p>
    </div>
</section>

<!-- FILTER BAR -->
<section class="filter-section">
    <form class="filter-form" method="GET" action="activities.php">

        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="search"
                placeholder="Rechercher une activité..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <input
            type="number"
            name="min_price"
            class="filter-input"
            placeholder="Prix min (MAD)"
            value="<?= $min_price > 0 ? $min_price : '' ?>"
            min="0"
            style="width:130px"
        >
        <input
            type="number"
            name="max_price"
            class="filter-input"
            placeholder="Prix max (MAD)"
            value="<?= $max_price < 9999 ? $max_price : '' ?>"
            min="0"
            style="width:130px"
        >

        <select name="rating" class="filter-select">
            <option value="0">Toutes les notes</option>
            <?php foreach ([4.5, 4, 3.5, 3] as $r): ?>
                <option value="<?= $r ?>" <?= $min_rating == $r ? 'selected' : '' ?>>
                    ⭐ <?= $r ?>+
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Mieux notées</option>
            <option value="price_asc"   <?= $sort === 'price_asc'   ? 'selected' : '' ?>>Prix croissant</option>
            <option value="price_desc"  <?= $sort === 'price_desc'  ? 'selected' : '' ?>>Prix décroissant</option>
            <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Nom A→Z</option>
        </select>

        <button type="submit" class="filter-btn">Filtrer</button>
        <a href="activities.php" class="filter-reset">✕ Réinitialiser</a>
    </form>
</section>

<!-- RESULTS BAR -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= $total ?></strong> activité<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?>
        <?php if ($search): ?> pour "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
    </p>
</div>

<!-- ACTIVITIES GRID -->
<div class="hotels-grid">
    <?php if (empty($activities)): ?>
        <div class="no-results">
            <span class="emoji">🏄</span>
            <h3>Aucune activité trouvée</h3>
            <p>Essayez de modifier vos filtres de recherche.</p>
            <a href="activities.php" class="btn-primary" style="display:inline-block;margin-top:1rem">Voir toutes les activités</a>
        </div>
    <?php else: ?>
        <?php foreach ($activities as $activity): ?>
        <div class="hotel-card">
            <div class="card-img">
                <img
                    src="../uploads/activities/<?= htmlspecialchars($activity['image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($activity['name']) ?>"
                    loading="lazy"
                >
                <span class="card-badge">⭐ <?= number_format($activity['rating'], 1) ?></span>
                <?php if (!empty($activity['duration'])): ?>
                    <span class="card-type">⏱ <?= htmlspecialchars($activity['duration']) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($activity['name']) ?></h3>
                <p class="location">📍 <?= htmlspecialchars($activity['location']) ?></p>
                <?php if (!empty($activity['description'])): ?>
                    <p class="description"><?= htmlspecialchars($activity['description']) ?></p>
                <?php endif; ?>
                <div class="card-footer">
                    <div class="price">
                        <?= number_format($activity['price'], 0, ',', ' ') ?> MAD
                        <span>/ personne</span>
                    </div>
                    <a href="activity-details.php?id=<?= $activity['id'] ?>" class="btn-card">Voir →</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- PAGINATION -->
<?php if ($total_pages > 1): ?>
<?php
$query_params = $_GET;
unset($query_params['page']);
$base_query = http_build_query($query_params);
$base_url   = 'activities.php?' . ($base_query ? $base_query . '&' : '');
?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="prev-next">← Précédent</a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($total_pages, $page + 2);
    if ($start > 1): ?><a href="<?= $base_url ?>page=1">1</a><?php endif;
    if ($start > 2): ?><span style="padding:0 .25rem;color:#aaa">…</span><?php endif;
    for ($i = $start; $i <= $end; $i++):
        if ($i === $page): ?><span class="active"><?= $i ?></span><?php
        else: ?><a href="<?= $base_url ?>page=<?= $i ?>"><?= $i ?></a><?php
        endif;
    endfor;
    if ($end < $total_pages - 1): ?><span style="padding:0 .25rem;color:#aaa">…</span><?php endif;
    if ($end < $total_pages): ?><a href="<?= $base_url ?>page=<?= $total_pages ?>"><?= $total_pages ?></a><?php endif;
    ?>

    <?php if ($page < $total_pages): ?>
        <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="prev-next">Suivant →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>