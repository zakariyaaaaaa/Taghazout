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
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 9999;
$min_rating = isset($_GET['rating'])    ? (float)$_GET['rating']   : 0;
$type       = isset($_GET['type'])      ? trim($_GET['type'])       : '';
$sort       = isset($_GET['sort'])      ? trim($_GET['sort'])       : 'rating_desc';

// ─── Build WHERE ───────────────────
//SELECT * FROM hotels WHERE 1=1───────────────────
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
if ($type !== '') {
    $t = $pdo->quote($type);
    $where .= " AND type = $t";
}

// ─── Sort ───────────────────────────────────────\/ ──────────
$order_map = [
    'rating_desc' => 'rating DESC',
    'price_asc'   => 'price ASC',
    'price_desc'  => 'price DESC',
    'name_asc'    => 'name ASC',
];
$order = $order_map[$sort] ?? 'rating DESC';

// ─── Count + Fetch ────────────────────────────────────────
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
    <title>Hôtels — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotels.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
            <video autoplay muted loop playsinline class="hero-video">
                    <source src="../assets/videos/hotel.mp4" type="video/mp4">
                </video>
    <div class="page-hero-content">
        <p class="eyebrow">🏨 Taghazout Platform</p>
        <h1>Nos Hôtels</h1>
        <p>Trouvez l'hébergement parfait pour votre séjour à Taghazout</p>
    </div>
</section>

<!-- FILTER BAR -->
<section class="filter-section">
    <form class="filter-form" method="GET" action="hotels.php">

        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="search"
                placeholder="Rechercher un hôtel..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <?php if (!empty($types)): ?>
        <select name="type" class="filter-select">
            <option value="">Tous les types</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($t)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

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
            <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Mieux notés</option>
            <option value="price_asc"   <?= $sort === 'price_asc'   ? 'selected' : '' ?>>Prix croissant</option>
            <option value="price_desc"  <?= $sort === 'price_desc'  ? 'selected' : '' ?>>Prix décroissant</option>
            <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Nom A→Z</option>
        </select>

        <button type="submit" class="filter-btn">Filtrer</button>
        <a href="hotels.php" class="filter-reset">✕ Réinitialiser</a>
    </form>
</section>

<!-- RESULTS BAR -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= $total ?></strong> hôtel<?= $total > 1 ? 's' : '' ?> trouvé<?= $total > 1 ? 's' : '' ?>
        <?php if ($search): ?> pour "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
    </p>
</div>

<!-- HOTELS GRID -->
<div class="hotels-grid">
    <?php if (empty($hotels)): ?>
        <div class="no-results">
            <span class="emoji">🏨</span>
            <h3>Aucun hôtel trouvé</h3>
            <p>Essayez de modifier vos filtres de recherche.</p>
            <a href="hotels.php" class="btn-primary" style="display:inline-block;margin-top:1rem">Voir tous les hôtels</a>
        </div>
    <?php else: ?>
        <?php foreach ($hotels as $hotel): ?>
        <div class="hotel-card">
            <div class="card-img">
                <img
                    src="../uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($hotel['name']) ?>"
                    loading="lazy"
                >
                <span class="card-badge">⭐ <?= number_format($hotel['rating'], 1) ?></span>
                <?php if (!empty($hotel['type'])): ?>
                    <span class="card-type"><?= htmlspecialchars($hotel['type']) ?></span>
                <?php endif; ?>

            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($hotel['name']) ?></h3>
                <p class="location">📍 <?= htmlspecialchars($hotel['location']) ?></p>
                <?php if (!empty($hotel['description'])): ?>
                    <p class="description"><?= htmlspecialchars($hotel['description']) ?></p>
                <?php endif; ?>
                <div class="card-footer">
                    <div class="price">
                        <?= number_format($hotel['price'], 0, ',', ' ') ?> MAD
                        <span>/ nuit</span>
                    </div>
                    <a href="hotel-details.php?id=<?= $hotel['id'] ?>" class="btn-card">Voir →</a>
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
$base_url   = 'hotels.php?' . ($base_query ? $base_query . '&' : '');
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
<script src="../assets/js/navbar.js"></script>

</script>
</body>
</html>