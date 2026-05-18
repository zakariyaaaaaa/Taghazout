<?php
require_once '../includes/header.php';
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorer la Carte — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotels.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <style>
        /* ── Layout ── */
        .map-page {
            display: flex;
            height: calc(100vh - 70px);
            overflow: hidden;
        }

        /* ── Sidebar ── */
        .map-sidebar {
            width: 360px;
            min-width: 360px;
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border-right: 1px solid rgba(14,165,233,0.1);
            overflow: hidden;
            z-index: 10;
        }
        .sidebar-header {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(14,165,233,0.08);
        }
        .sidebar-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: .25rem;
        }
        .sidebar-sub {
            font-size: .82rem;
            color: var(--text-light);
        }

        /* ── Search ── */
        .sidebar-search {
            padding: .75rem 1.5rem;
            border-bottom: 1px solid rgba(14,165,233,0.08);
        }
        .search-wrap {
            position: relative;
        }
        .search-wrap input {
            width: 100%;
            padding: .55rem 1rem .55rem 2.4rem;
            border-radius: 50px;
            border: 1px solid rgba(14,165,233,0.15);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color .2s;
        }
        .search-wrap input:focus { border-color: var(--primary); }
        .search-icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1rem;
        }

        /* ── Filters ── */
        .sidebar-filters {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            padding: .75rem 1.5rem;
            border-bottom: 1px solid rgba(14,165,233,0.08);
        }
        .filter-chip {
            padding: .3rem .85rem;
            border-radius: 50px;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid rgba(14,165,233,0.15);
            background: var(--bg-primary);
            color: var(--text-light);
            transition: all .2s;
            white-space: nowrap;
        }
        .filter-chip:hover,
        .filter-chip.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ── Results list ── */
        .sidebar-results {
            flex: 1;
            overflow-y: auto;
            padding: .75rem 1rem;
        }
        .results-count {
            font-size: .8rem;
            color: var(--text-light);
            padding: 0 .5rem .5rem;
        }
        .place-card {
            display: flex;
            gap: .85rem;
            padding: .75rem .85rem;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background .15s;
            border: 1px solid transparent;
            margin-bottom: .4rem;
        }
        .place-card:hover,
        .place-card.active {
            background: rgba(14,165,233,0.06);
            border-color: rgba(14,165,233,0.15);
        }
        .place-thumb {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--bg-primary);
        }
        .place-info { flex: 1; min-width: 0; }
        .place-name {
            font-family: 'Syne', sans-serif;
            font-size: .9rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .place-cat {
            font-size: .72rem;
            color: var(--primary);
            font-weight: 600;
            margin: .15rem 0 .3rem;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .place-desc {
            font-size: .78rem;
            color: var(--text-light);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .no-results {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
            font-size: .9rem;
        }
        .no-results span { display: block; font-size: 2rem; margin-bottom: .75rem; }

        /* ── Map ── */
        .map-wrap {
            flex: 1;
            position: relative;
        }
        #map {
            width: 100%;
            height: 100%;
        }

        /* ── Detail Panel (slides in over map) ── */
        .detail-panel {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 300px;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
            overflow: hidden;
            transform: translateX(120%);
            transition: transform .3s ease;
            z-index: 500;
        }
        .detail-panel.open { transform: translateX(0); }
        .detail-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .detail-body { padding: 1rem 1.2rem; }
        .detail-cat {
            font-size: .72rem;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: .3rem;
        }
        .detail-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: .4rem;
        }
        .detail-desc {
            font-size: .82rem;
            color: var(--text-light);
            line-height: 1.55;
            margin-bottom: .85rem;
        }
        .detail-actions { display: flex; gap: .5rem; }
        .btn-detail {
            flex: 1;
            padding: .5rem;
            border-radius: 8px;
            font-size: .8rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .2s;
        }
        .btn-detail:hover { opacity: .85; }
        .btn-primary-sm { background: var(--primary); color: #fff; }
        .btn-ghost-sm { border: 1px solid rgba(14,165,233,0.2); color: var(--text-primary); }
        .detail-close {
            position: absolute;
            top: .6rem;
            right: .6rem;
            background: rgba(0,0,0,.45);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Marker styles ── */
        .map-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
            cursor: pointer;
            transition: transform .2s;
        }
        .map-marker:hover { transform: scale(1.15); }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .map-page { flex-direction: column; height: auto; }
            .map-sidebar { width: 100%; min-width: unset; height: auto; max-height: 45vh; }
            .map-wrap { height: 55vh; }
            .detail-panel { width: calc(100% - 2rem); }
        }
    </style>
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<!-- PAGE HERO (compact) -->
<section class="page-hero" style="min-height:180px;">
            <video autoplay muted loop playsinline class="hero-video">
                    <source src="../assets/videos/explorer-la-carte.mp4" type="video/mp4">
                </video>
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">🗺️ Taghazout Platform</p>
        <h1>Explorer la Carte</h1>
        <p>Hôtels, surf, restaurants et activités — tout en un coup d'œil</p>
    </div>
</section>

<!-- MAP PAGE -->
<div class="map-page">

    <!-- SIDEBAR -->
    <aside class="map-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Lieux à Taghazout</div>
            <div class="sidebar-sub" id="count-label">Chargement...</div>
        </div>

        <div class="sidebar-search">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="search-input" placeholder="Rechercher un lieu...">
            </div>
        </div>

        <div class="sidebar-filters" id="filter-bar">
            <!-- injected by JS from map-filters.php -->
        </div>

        <div class="sidebar-results" id="places-list">
            <div class="no-results"><span>⏳</span>Chargement des lieux...</div>
        </div>
    </aside>

    <!-- MAP -->
    <div class="map-wrap">
        <div id="map"></div>

        <!-- DETAIL PANEL -->
        <div class="detail-panel" id="detail-panel">
            <button class="detail-close" onclick="closeDetail()">✕</button>
            <img src="" id="d-img" class="detail-img" alt="" onerror="this.src='../assets/images/default.jpg'">
            <div class="detail-body">
                <div class="detail-cat" id="d-cat"></div>
                <div class="detail-title" id="d-title"></div>
                <div class="detail-desc" id="d-desc"></div>
                <div class="detail-actions">
                    <a href="#" id="d-link" class="btn-detail btn-primary-sm">Voir détails</a>
                    <button class="btn-detail btn-ghost-sm" onclick="flyToActive()">📍 Centrer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ── Config ──────────────────────────────────────────────────
const CAT_COLORS = {
    hotel:      '#185FA5',
    activity:   '#7F77DD',
    surf:       '#1D9E75',
    restaurant: '#D85A30',
    general:    '#888780',
};
const CAT_ICONS = {
    hotel: '🏨', activity: '🏄', surf: '🌊', restaurant: '🍽️', general: '📷',
};
const CAT_LABELS = {
    hotel: 'Hôtels', activity: 'Activités', surf: 'Surf', restaurant: 'Restaurants', general: 'Général',
};

// ── State ────────────────────────────────────────────────────
let allPlaces   = [];
let allFilters  = [];
let activeFilter = 'all';
let activeIndex  = null;
let markers      = [];

// ── Map init ─────────────────────────────────────────────────
const map = L.map('map', { zoomControl: true, scrollWheelZoom: true })
             .setView([30.533, -9.707], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18,
}).addTo(map);

// ── Load filters ─────────────────────────────────────────────
async function loadFilters() {
    const res  = await fetch('map-filters.php');
    const data = await res.json();
    allFilters = data;
    renderFilterBar();
}

function renderFilterBar() {
    const bar = document.getElementById('filter-bar');
    bar.innerHTML = '';
    [{ value: 'all', label: '🌍 Tout' }, ...allFilters.map(f => ({
        value: f.category,
        label: (CAT_ICONS[f.category] || '📌') + ' ' + (CAT_LABELS[f.category] || f.category) + ' <sup>' + f.count + '</sup>',
    }))].forEach(f => {
        const btn = document.createElement('button');
        btn.className = 'filter-chip' + (f.value === activeFilter ? ' active' : '');
        btn.innerHTML = f.label;
        btn.onclick = () => setFilter(f.value);
        bar.appendChild(btn);
    });
}

// ── Load places ───────────────────────────────────────────────
async function loadPlaces() {
    const params = new URLSearchParams();
    if (activeFilter !== 'all') params.set('category', activeFilter);
    const q = document.getElementById('search-input').value.trim();
    if (q) params.set('q', q);

    const res  = await fetch('map-data.php?' + params.toString());
    allPlaces  = await res.json();
    renderList();
    renderMarkers();
}

// ── Render sidebar list ───────────────────────────────────────
function renderList() {
    const list = document.getElementById('places-list');
    document.getElementById('count-label').textContent = allPlaces.length + ' lieu' + (allPlaces.length !== 1 ? 'x' : '') + ' trouvé' + (allPlaces.length !== 1 ? 's' : '');

    if (!allPlaces.length) {
        list.innerHTML = '<div class="no-results"><span>🔍</span>Aucun lieu trouvé.</div>';
        return;
    }
    list.innerHTML = allPlaces.map((p, i) => `
        <div class="place-card" id="card-${i}" onclick="selectPlace(${i})">
            <img class="place-thumb"
                 src="../uploads/gallery/${p.image || ''}"
                 onerror="this.src='../assets/images/default.jpg'"
                 alt="${escHtml(p.title)}">
            <div class="place-info">
                <div class="place-name">${escHtml(p.title)}</div>
                <div class="place-cat">${CAT_ICONS[p.category] || '📌'} ${CAT_LABELS[p.category] || p.category}</div>
                <div class="place-desc">${escHtml(p.description || '')}</div>
            </div>
        </div>
    `).join('');
}

// ── Render map markers ────────────────────────────────────────
function renderMarkers() {
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    allPlaces.forEach((p, i) => {
        if (!p.latitude || !p.longitude) return;
        const color = CAT_COLORS[p.category] || '#888';
        const icon  = CAT_ICONS[p.category]  || '📌';

        const divIcon = L.divIcon({
            className: '',
            html: `<div class="map-marker" style="background:${color}">${icon}</div>`,
            iconSize:   [36, 36],
            iconAnchor: [18, 18],
            popupAnchor:[0, -22],
        });

        const m = L.marker([p.latitude, p.longitude], { icon: divIcon })
            .addTo(map)
            .on('click', () => selectPlace(i));

        markers.push(m);
    });
}

// ── Select a place ────────────────────────────────────────────
function selectPlace(i) {
    // sidebar highlight
    document.querySelectorAll('.place-card').forEach(c => c.classList.remove('active'));
    const card = document.getElementById('card-' + i);
    if (card) { card.classList.add('active'); card.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }

    activeIndex = i;
    const p = allPlaces[i];

    // fly map
    if (p.latitude && p.longitude) {
        map.flyTo([p.latitude, p.longitude], 16, { duration: .8 });
    }

    // fill detail panel
    const linkMap = {
        hotel:      `hotel.php?id=${p.id}`,
        restaurant: `restaurant.php?id=${p.id}`,
        activity:   `activity.php?id=${p.id}`,
        surf:       `surf.php?id=${p.id}`,
    };
    document.getElementById('d-img').src   = '../uploads/gallery/' + (p.image || '');
    document.getElementById('d-cat').textContent   = (CAT_ICONS[p.category] || '') + ' ' + (CAT_LABELS[p.category] || p.category);
    document.getElementById('d-title').textContent = p.title;
    document.getElementById('d-desc').textContent  = p.description || '';
    document.getElementById('d-link').href          = linkMap[p.category] || '#';
    document.getElementById('detail-panel').classList.add('open');
}

function closeDetail() {
    document.getElementById('detail-panel').classList.remove('open');
    document.querySelectorAll('.place-card').forEach(c => c.classList.remove('active'));
    activeIndex = null;
}

function flyToActive() {
    if (activeIndex === null) return;
    const p = allPlaces[activeIndex];
    if (p?.latitude && p?.longitude) map.flyTo([p.latitude, p.longitude], 16);
}

// ── Filter ────────────────────────────────────────────────────
function setFilter(val) {
    activeFilter = val;
    renderFilterBar();
    loadPlaces();
}

// ── Search ────────────────────────────────────────────────────
let searchTimer;
document.getElementById('search-input').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadPlaces, 300);
});

// ── Utils ─────────────────────────────────────────────────────
function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Boot ──────────────────────────────────────────────────────
loadFilters();
loadPlaces();
</script>
</body>
</html>