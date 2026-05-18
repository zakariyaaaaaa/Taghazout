<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Pagination ───────────────────────────────────────────
$per_page = 9;
$page     = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

// ─── Filters ──────────────────────────────────────────────
$search    = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$level     = isset($_GET['level'])    ? trim($_GET['level'])    : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 9999;
$sort      = isset($_GET['sort'])     ? trim($_GET['sort'])     : 'price_asc';

// ─── Build WHERE ──────────────────────────────────────────
$where = "WHERE 1=1";

if ($search !== '') {
    $s = $pdo->quote("%$search%");
    $where .= " AND (title LIKE $s OR description LIKE $s)";
}
if ($level !== '') {
    $l = $pdo->quote($level);
    $where .= " AND level = $l";
}
if ($min_price > 0) {
    $where .= " AND price >= " . (int)$min_price;
}
if ($max_price < 9999) {
    $where .= " AND price <= " . (int)$max_price;
}

// ─── Sort ─────────────────────────────────────────────────
$order_map = [
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'name_asc'   => 'title ASC',
];
$order = $order_map[$sort] ?? 'price ASC';

// ─── Count + Fetch ────────────────────────────────────────
$total        = $pdo->query("SELECT COUNT(*) FROM surf_courses $where")->fetchColumn();
$total_pages  = ceil($total / $per_page);
$surf_courses = $pdo->query("SELECT * FROM surf_courses $where ORDER BY $order LIMIT $per_page OFFSET $offset")->fetchAll();

// ─── Level labels ─────────────────────────────────────────
$level_labels = [
    'beginner'     => '🟢 Débutant',
    'intermediate' => '🟡 Intermédiaire',
    'advanced'     => '🔴 Avancé',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours de Surf — Taghazout Platform</title>
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
                    <source src="../assets/videos/surf.mp4" type="video/mp4">
                </video>
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">🏄 Taghazout Platform</p>
        <h1>Cours de Surf</h1>
        <p>Apprenez à surfer avec nos instructeurs professionnels à Taghazout</p>
    </div>
</section>

<!-- FILTER BAR -->
<section class="filter-section">
    <form class="filter-form" method="GET" action="surf-courses.php">

        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="search"
                placeholder="Rechercher un cours..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <select name="level" class="filter-select">
            <option value="">Tous les niveaux</option>
            <?php foreach ($level_labels as $val => $label): ?>
                <option value="<?= $val ?>" <?= $level === $val ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>

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

        <select name="sort" class="filter-select">
            <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Prix croissant</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            <option value="name_asc"   <?= $sort === 'name_asc'   ? 'selected' : '' ?>>Nom A→Z</option>
        </select>

        <button type="submit" class="filter-btn">Filtrer</button>
        <a href="surf-courses.php" class="filter-reset">✕ Réinitialiser</a>
    </form>
</section>

<!-- RESULTS BAR -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= $total ?></strong> cours trouvé<?= $total > 1 ? 's' : '' ?>
        <?php if ($search): ?> pour "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
    </p>
</div>

<!-- SURF COURSES GRID -->
<div class="hotels-grid">
    <?php if (empty($surf_courses)): ?>
        <div class="no-results">
            <span class="emoji">🏄</span>
            <h3>Aucun cours trouvé</h3>
            <p>Essayez de modifier vos filtres de recherche.</p>
            <a href="surf-courses.php" class="btn-primary" style="display:inline-block;margin-top:1rem">Voir tous les cours</a>
        </div>
    <?php else: ?>
        <?php foreach ($surf_courses as $course): ?>
        <div class="hotel-card">
            <div class="card-img">
                <img
                    src="../assets/images/surf/<?= htmlspecialchars($course['image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($course['title']) ?>"
                    loading="lazy"
                >
                <?php if (!empty($course['level'])): ?>
                    <span class="card-badge"><?= $level_labels[$course['level']] ?? $course['level'] ?></span>
                <?php endif; ?>
                <?php if (!empty($course['duration'])): ?>
                    <span class="card-type">⏱ <?= htmlspecialchars($course['duration']) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($course['title']) ?></h3>
                <p class="location">👥 Max <?= $course['max_students'] ?> étudiants</p>
                <?php if (!empty($course['description'])): ?>
                    <p class="description"><?= htmlspecialchars($course['description']) ?></p>
                <?php endif; ?>
                <div class="card-footer">
                    <div class="price">
                        <?= number_format($course['price'], 0, ',', ' ') ?> MAD
                        <span>/ cours</span>
                    </div>
                    <a href="Surf-course-details.php?id=<?= $course['id'] ?>" class="btn-card">Voir →</a>
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
$base_url   = 'surf-courses.php?' . ($base_query ? $base_query . '&' : '');
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