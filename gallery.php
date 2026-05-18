<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Filters ──────────────────────────────────────────────
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ─── Build WHERE ──────────────────────────────────────────
$where = "WHERE 1=1";
if ($category !== '') {
    $c = $pdo->quote($category);
    $where .= " AND category = $c";
}

// ─── Fetch gallery ────────────────────────────────────────
$photos = $pdo->query("SELECT * FROM gallery $where ORDER BY created_at DESC")->fetchAll();
$total  = count($photos);

// ─── Categories ───────────────────────────────────────────
$categories = [
    ''           => '🌍 Tout',
    'hotel'      => '🏨 Hôtels',
    'activity'   => '🏄 Activités',
    'surf'       => '🌊 Surf',
    'restaurant' => '🍽️ Restaurants',
    'general'    => '📷 Général',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotels.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <style>
        .gallery-tabs {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            justify-content: center;
            padding: 2rem 1.5rem 1rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .gallery-tab {
            padding: .5rem 1.2rem;
            border-radius: 50px;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
            background: var(--card-bg);
            color: var(--text-light);
            border: 1px solid rgba(14,165,233,0.1);
            transition: all .2s;
        }
        .gallery-tab:hover,
        .gallery-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-width: 1200px;
            margin: 1.5rem auto 4rem;
            padding: 0 1.5rem;
        }
        .gallery-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            background: var(--card-bg);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }
        .gallery-item:hover img {
            transform: scale(1.06);
        }
        .gallery-item-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .3s;
            display: flex;
            align-items: flex-end;
            padding: 1rem;
        }
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        .gallery-item-title {
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: .95rem;
        }
        .gallery-item-cat {
            position: absolute;
            top: .75rem;
            right: .75rem;
            background: rgba(0,0,0,.5);
            color: #fff;
            font-size: .75rem;
            padding: .25rem .6rem;
            border-radius: 50px;
            backdrop-filter: blur(4px);
        }
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .lightbox.open {
            display: flex;
        }
        .lightbox img {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: var(--radius);
            object-fit: contain;
            box-shadow: 0 25px 60px rgba(0,0,0,.5);
        }
        .lightbox-close {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255,255,255,.15);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }
        .lightbox-close:hover { background: rgba(255,255,255,.3); }
        .lightbox-caption {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            background: rgba(0,0,0,.5);
            padding: .5rem 1.2rem;
            border-radius: 50px;
            backdrop-filter: blur(4px);
        }
        .lightbox-nav {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,.15);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,.3); }
        .lightbox-prev { left: 1.5rem; }
        .lightbox-next { right: 1.5rem; }
        .no-photos {
            text-align: center;
            padding: 5rem 2rem;
            grid-column: 1/-1;
            color: var(--text-light);
        }
        .no-photos .emoji { font-size: 3rem; display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">📷 Taghazout Platform</p>
        <h1>Notre Galerie</h1>
        <p>Découvrez Taghazout en images — plages, surf, hôtels et gastronomie</p>
    </div>
</section>

<!-- CATEGORY TABS -->
<div class="gallery-tabs">
    <?php foreach ($categories as $val => $label): ?>
        <a
            href="gallery.php<?= $val ? '?category=' . urlencode($val) : '' ?>"
            class="gallery-tab <?= $category === $val ? 'active' : '' ?>"
        >
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- RESULTS COUNT -->
<div class="results-bar">
    <p class="results-count">
        <strong><?= $total ?></strong> photo<?= $total > 1 ? 's' : '' ?>
        <?php if ($category && isset($categories[$category])): ?>
            dans <em><?= $categories[$category] ?></em>
        <?php endif; ?>
    </p>
</div>

<!-- GALLERY GRID -->
<div class="gallery-grid">
    <?php if (empty($photos)): ?>
        <div class="no-photos">
            <span class="emoji">📷</span>
            <h3>Aucune photo disponible</h3>
            <p>Revenez bientôt pour découvrir nos photos.</p>
        </div>
    <?php else: ?>
        <?php foreach ($photos as $i => $photo): ?>
        <div
            class="gallery-item"
            onclick="openLightbox(<?= $i ?>)"
        >
            <img
                src="../uploads/gallery/<?= htmlspecialchars($photo['image']) ?>"
                onerror="this.src='../assets/images/default.jpg'"
                alt="<?= htmlspecialchars($photo['title'] ?? '') ?>"
                loading="lazy"
            >
            <span class="gallery-item-cat"><?= $categories[$photo['category']] ?? $photo['category'] ?></span>
            <?php if (!empty($photo['title'])): ?>
            <div class="gallery-item-overlay">
                <span class="gallery-item-title"><?= htmlspecialchars($photo['title']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <button class="lightbox-nav lightbox-prev" onclick="event.stopPropagation(); navigate(-1)">‹</button>
    <img src="" id="lightbox-img" alt="">
    <button class="lightbox-nav lightbox-next" onclick="event.stopPropagation(); navigate(1)">›</button>
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
const photos = <?= json_encode(array_map(fn($p) => [
    'src'   => '../uploads/gallery/' . ($p['image'] ?? ''),
    'title' => $p['title'] ?? '',
], $photos)) ?>;

let current = 0;

function openLightbox(index) {
    current = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('lightbox')) return;
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

function navigate(dir) {
    current = (current + dir + photos.length) % photos.length;
    updateLightbox();
}

function updateLightbox() {
    const p = photos[current];
    document.getElementById('lightbox-img').src        = p.src;
    document.getElementById('lightbox-caption').textContent = p.title || '';
}

// Keyboard navigation
document.addEventListener('keydown', e => {
    if (!document.getElementById('lightbox').classList.contains('open')) return;
    if (e.key === 'ArrowRight') navigate(1);
    if (e.key === 'ArrowLeft')  navigate(-1);
    if (e.key === 'Escape')     { document.getElementById('lightbox').classList.remove('open'); document.body.style.overflow = ''; }
});
</script>
</body>
</html>