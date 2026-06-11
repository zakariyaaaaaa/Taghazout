<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Pagination ───────────────────────────────────────────
$per_page = 9;
$page     = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

// ─── Filters ──────────────────────────────────────────────
$search      = isset($_GET['search'])      ? trim($_GET['search'])      : '';
$cuisine     = isset($_GET['cuisine'])     ? trim($_GET['cuisine'])     : '';
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : '';
$min_rating  = isset($_GET['rating'])      ? (float)$_GET['rating']    : 0;
$sort        = isset($_GET['sort'])        ? trim($_GET['sort'])        : 'rating_desc';

// ─── Build WHERE ──────────────────────────────────────────
$where = "WHERE 1=1";

if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (name LIKE $s OR location LIKE $s OR cuisine LIKE $s)";
}
if ($cuisine !== '') {
    $c = $pdo->quote("%$cuisine%");
    $where .= " AND cuisine LIKE $c";
}
if ($price_range !== '') {
    $p = $pdo->quote($price_range);
    $where .= " AND price_range = $p";
}
if ($min_rating > 0) {
    $where .= " AND rating >= " . (float)$min_rating;
}

// ─── Sort ─────────────────────────────────────────────────
$order_map = [
    'rating_desc' => 'rating DESC',
    'rating_asc'  => 'rating ASC',
    'name_asc'    => 'name ASC',
];
$order = $order_map[$sort] ?? 'rating DESC';

// ─── Count + Fetch ────────────────────────────────────────
$total       = $pdo->query("SELECT COUNT(*) FROM restaurants $where")->fetchColumn();
$total_pages = ceil($total / $per_page);
$restaurants = $pdo->query("SELECT * FROM restaurants $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();

// ─── Distinct cuisines ────────────────────────────────────
$cuisines = $pdo->query("SELECT DISTINCT cuisine FROM restaurants WHERE cuisine IS NOT NULL ORDER BY cuisine")->fetchAll(PDO::FETCH_COLUMN);

// ─── Price range labels ───────────────────────────────────
$price_labels = [
    'cheap'     => '💰 Pas cher',
    'moderate'  => '💰💰 Modéré',
    'expensive' => '💰💰💰 Haut de gamme',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants — Taghazout Platform</title>
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
                    <source src="../assets/videos/restaurants.mp4" type="video/mp4">
                </video>
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">🍽️ Taghazout Platform</p>
        <h1>Nos Restaurants</h1>
        <p>Découvrez les meilleures tables de Taghazout</p>
    </div>
</section>

<!-- FILTER BAR -->
<section class="filter-section">
    <form class="filter-form" method="GET" action="restaurants.php">

        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="search"
                placeholder="Rechercher un restaurant..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <?php if (!empty($cuisines)): ?>
        <select name="cuisine" class="filter-select">
            <option value="">Toutes les cuisines</option>
            <?php foreach ($cuisines as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $cuisine === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="price_range" class="filter-select">
            <option value="">Tous les prix</option>
            <?php foreach ($price_labels as $val => $label): ?>
                <option value="<?= $val ?>" <?= $price_range === $val ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="rating" class="filter-select">
            <option value="0">Toutes les notes</option>
            <?php foreach ([4.5, 4, 3.5, 3] as $r): ?>
                <option value="<?= $r ?>" <?= $min_rating == $r ? 'selected' : '' ?>>
                    ⭐ <?= $r ?>+
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Mieux notés</option>
            <option value="rating_asc"  <?= $sort === 'rating_asc'  ? 'selected' : '' ?>>Moins bien notés</option>
            <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Nom A→Z</option>
        </select>

        <button type="submit" class="filter-btn">Filtrer</button>
        <a href="restaurants.php" class="filter-reset">✕ Réinitialiser</a>
    </form>
</section>

<!-- RESULTS BAR -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= $total ?></strong> restaurant<?= $total > 1 ? 's' : '' ?> trouvé<?= $total > 1 ? 's' : '' ?>
        <?php if ($search): ?> pour "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
    </p>
</div>

<!-- RESTAURANTS GRID -->
<div class="hotels-grid">
    <?php if (empty($restaurants)): ?>
        <div class="no-results">
            <span class="emoji">🍽️</span>
            <h3>Aucun restaurant trouvé</h3>
            <p>Essayez de modifier vos filtres de recherche.</p>
            <a href="restaurants.php" class="btn-primary" style="display:inline-block;margin-top:1rem">Voir tous les restaurants</a>
        </div>
    <?php else: ?>
        <?php foreach ($restaurants as $restaurant): ?>
        <div class="hotel-card">
            <div class="card-img">
                <img
                    src="../uploads/restaurants/<?= htmlspecialchars($restaurant['image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($restaurant['name']) ?>"
                    loading="lazy"
                >
                <span class="card-badge">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                <?php if (!empty($restaurant['price_range'])): ?>
                    <span class="card-type"><?= $price_labels[$restaurant['price_range']] ?? $restaurant['price_range'] ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                <p class="location">📍 <?= htmlspecialchars($restaurant['location']) ?></p>
                <?php if (!empty($restaurant['cuisine'])): ?>
                    <p class="location">🍴 <?= htmlspecialchars($restaurant['cuisine']) ?></p>
                <?php endif; ?>
                <?php if (!empty($restaurant['description'])): ?>
                    <p class="description"><?= htmlspecialchars($restaurant['description']) ?></p>
                <?php endif; ?>
                <div class="card-footer">
                    <div class="price" style="font-size:.95rem;">
                        <?= $price_labels[$restaurant['price_range']] ?? '—' ?>
                    </div>
                    <a href="restaurant-details.php?id=<?= $restaurant['id'] ?>" class="btn-card">Voir →</a>
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
$base_url   = 'restaurants.php?' . ($base_query ? $base_query . '&' : '');
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