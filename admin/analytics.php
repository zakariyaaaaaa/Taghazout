<?php
session_start();
require_once '../includes/config.php';

// ─── Admin check ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ─── Period filter ────────────────────────────────────────
$period = $_GET['period'] ?? '6';  // months: 1, 3, 6, 12
$period = in_array($period, ['1','3','6','12']) ? intval($period) : 6;

// ─── KPI Summary ─────────────────────────────────────────
$kpi = [
    'total_revenue'   => $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='accepted'")->fetchColumn(),
    'revenue_period'  => $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='accepted' AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)"),
    'total_bookings'  => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'accepted'        => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='accepted'")->fetchColumn(),
    'pending'         => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
    'rejected'        => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='rejected'")->fetchColumn(),
    'total_users'     => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'new_users'       => $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='user' AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)"),
    'avg_booking'     => $pdo->query("SELECT COALESCE(AVG(total_price),0) FROM bookings WHERE status='accepted'")->fetchColumn(),
    'surf_bookings'   => $pdo->query("SELECT COUNT(*) FROM bookings WHERE type='surf'")->fetchColumn(),
    'hotel_bookings'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE type='hotel'")->fetchColumn(),
    'total_reviews'   => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'avg_rating'      => $pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews")->fetchColumn(),
];

$kpi['revenue_period']->execute([$period]);
$kpi['revenue_period'] = $kpi['revenue_period']->fetchColumn();

$kpi['new_users']->execute([$period]);
$kpi['new_users'] = $kpi['new_users']->fetchColumn();

// ─── Revenue by month ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '%b %Y') AS month,
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        SUM(total_price) AS revenue,
        COUNT(*) AS bookings
    FROM bookings
    WHERE status = 'accepted'
    AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month_key ASC
");
$stmt->execute([$period]);
$monthly = $stmt->fetchAll();

// ─── Bookings by type ─────────────────────────────────────
$by_type = $pdo->query("
    SELECT type, COUNT(*) AS cnt, COALESCE(SUM(total_price),0) AS revenue
    FROM bookings
    GROUP BY type
")->fetchAll();

// ─── Bookings by status ───────────────────────────────────
$by_status = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM bookings
    GROUP BY status
")->fetchAll();

// ─── Top surf courses ─────────────────────────────────────
$top_surf = $pdo->query("
    SELECT s.title, s.price, s.level,
           COUNT(b.id) AS bookings,
           COALESCE(SUM(b.total_price),0) AS revenue
    FROM surf_courses s
    LEFT JOIN bookings b ON b.reference_id = s.id AND b.type = 'surf'
    GROUP BY s.id
    ORDER BY bookings DESC
    LIMIT 5
")->fetchAll();

// ─── Top hotels ───────────────────────────────────────────
$top_hotels = $pdo->query("
    SELECT h.name, h.location,
           COUNT(b.id) AS bookings,
           COALESCE(SUM(b.total_price),0) AS revenue
    FROM hotels h
    LEFT JOIN bookings b ON b.reference_id = h.id AND b.type = 'hotel'
    GROUP BY h.id
    ORDER BY bookings DESC
    LIMIT 5
")->fetchAll();
// ─── New users by month ───────────────────────────────────
$stmt2 = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           DATE_FORMAT(created_at, '%Y-%m') AS month_key,
           COUNT(*) AS cnt
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month_key ASC
");
$stmt2->execute([$period]);
$monthly_users = $stmt2->fetchAll();

// ─── Reviews breakdown ────────────────────────────────────
$ratings_dist = $pdo->query("
    SELECT rating, COUNT(*) AS cnt
    FROM reviews
    GROUP BY rating
    ORDER BY rating DESC
")->fetchAll();

// ─── Conversion rate ──────────────────────────────────────
$conversion = $kpi['total_bookings'] > 0
    ? round(($kpi['accepted'] / $kpi['total_bookings']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — Taghazout Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── Analytics-specific styles ─────────────────── */
        .analytics-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: var(--radius, 12px);
            padding: 1.25rem 1.4rem;
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px; height: 100%;
            border-radius: 4px 0 0 4px;
        }
        .kpi-card.accent-teal::before  { background: #0ea5e9; }
        .kpi-card.accent-green::before { background: #10b981; }
        .kpi-card.accent-amber::before { background: #f59e0b; }
        .kpi-card.accent-violet::before { background: #8b5cf6; }
        .kpi-card.accent-rose::before   { background: #ef4444; }
        .kpi-card.accent-surf::before   { background: #06b6d4; }

        .kpi-label {
            font-size: 0.78rem;
            color: var(--text-light, #6b7280);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
        }

        .kpi-value {
            font-family: 'Syne', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--text, #111);
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .kpi-sub {
            font-size: 0.78rem;
            color: var(--text-light, #6b7280);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .kpi-sub .up   { color: #10b981; font-weight: 600; }
        .kpi-sub .down { color: #ef4444; font-weight: 600; }
        .kpi-sub .neutral { color: #f59e0b; font-weight: 600; }

        .kpi-icon {
            position: absolute;
            top: 1.1rem; right: 1.1rem;
            font-size: 1.5rem;
            opacity: 0.18;
        }

        /* ── Charts grid ─────────────────────────────── */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .charts-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        /* ── Period selector ─────────────────────────── */
        .period-selector {
            display: flex;
            gap: 0.4rem;
            background: var(--bg, #f9fafb);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 8px;
            padding: 0.3rem;
        }

        .period-btn {
            padding: 0.35rem 0.9rem;
            border-radius: 6px;
            border: none;
            background: transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-light, #6b7280);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .period-btn.active,
        .period-btn:hover {
            background: var(--card-bg, #fff);
            color: var(--primary, #0ea5e9);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .period-btn.active { font-weight: 600; }

        /* ── Top table ───────────────────────────────── */
        .top-table { width: 100%; border-collapse: collapse; }
        .top-table th {
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-light, #6b7280);
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid var(--border, #e5e7eb);
        }
        .top-table td {
            padding: 0.75rem 0.8rem;
            font-size: 0.875rem;
            color: var(--text, #111);
            border-bottom: 1px solid var(--border, #e5e7eb);
        }
        .top-table tr:last-child td { border-bottom: none; }
        .top-table tr:hover td { background: var(--bg, #f9fafb); }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .rank-1 { background: #fef3c7; color: #d97706; }
        .rank-2 { background: #f1f5f9; color: #64748b; }
        .rank-3 { background: #fef2f2; color: #dc2626; }
        .rank-other { background: var(--bg, #f9fafb); color: var(--text-light, #6b7280); }

        /* ── Progress bar ────────────────────────────── */
        .progress-bar-wrap { margin-bottom: 0.9rem; }
        .progress-bar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3rem;
            font-size: 0.82rem;
        }
        .progress-bar-track {
            height: 7px;
            background: var(--border, #e5e7eb);
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        /* ── Conversion ring ─────────────────────────── */
        .conversion-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
            gap: 0.5rem;
        }
        .conversion-ring {
            position: relative;
            width: 120px; height: 120px;
        }
        .conversion-ring svg { transform: rotate(-90deg); }
        .ring-text {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .ring-pct {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text, #111);
            line-height: 1;
        }
        .ring-label {
            font-size: 0.7rem;
            color: var(--text-light, #6b7280);
        }

        @media (max-width: 1100px) {
            .analytics-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .charts-grid-3 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .analytics-kpi-grid { grid-template-columns: 1fr 1fr; }
            .charts-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="admin-shell">

<!-- ══════════════════════════════
     SIDEBAR (identical to dashboard)
══════════════════════════════ -->
<aside class="admin-sidebar">

    <div class="sb-logo">
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text">
            <strong>Taghazout</strong>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="sb-label">Dashboard</div>

    <nav class="sb-nav">
        <a href="dashboard.php">
            <span class="nav-icon">📊</span><span>Dashboard</span>
        </a>
        <a href="analytics.php" class="active">
            <span class="nav-icon">📈</span><span>Analytics</span>
        </a>

        <div class="sb-label">Contenu</div>

        <a href="hotels/hotels.php">
            <span class="nav-icon">🏨</span><span>Hotels</span>
        </a>
        <a href="activities/activities.php">
            <span class="nav-icon">🎯</span><span>Activities</span>
        </a>
        <a href="surf-courses/courses.php">
            <span class="nav-icon">🏄</span><span>Surf Courses</span>
        </a>
        <a href="restaurants/restaurants.php">
            <span class="nav-icon">🍽️</span><span>Restaurants</span>
        </a>

        <div class="sb-label">Gestion</div>

        <a href="bookings/bookings.php">
            <span class="nav-icon">📅</span><span>Bookings</span>
            <?php if ($kpi['pending'] > 0): ?>
            <span class="nav-badge"><?= $kpi['pending'] ?></span>
            <?php endif; ?>
        </a>
        <a href="payments/payments.php">
            <span class="nav-icon">💳</span><span>Payments</span>
        </a>
        <a href="users/users.php">
            <span class="nav-icon">👥</span><span>Users</span>
        </a>
        <a href="reviews/reviews.php">
            <span class="nav-icon">⭐</span><span>Reviews</span>
        </a>
        <a href="messages/messages.php">
            <span class="nav-icon">💬</span><span>Messages</span>
        </a>
    </nav>

    <div class="sb-admin">
        <img src="../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../auth/logout.php" class="sb-logout">🚪</a>
    </div>

</aside>

<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="admin-main">

<div class="admin-topbar">
    <div class="topbar-left">
        <div>
            <div class="topbar-title">Analytics</div>
            <div class="topbar-breadcrumb">
                Home <span>/</span> Analytics
            </div>
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
            <h1>Analytics</h1>
            <p>Vue d'ensemble de la performance — Taghazout 🌊</p>
        </div>
        <div style="display:flex; gap:0.6rem; align-items:center;">
            <!-- Period selector -->
            <div class="period-selector">
                <?php foreach (['1'=>'1M','3'=>'3M','6'=>'6M','12'=>'1A'] as $val => $label): ?>
                <a href="?period=<?= $val ?>"
                   class="period-btn <?= $period == $val ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
            <span style="font-size:0.82rem; color:var(--text-light);"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- ── KPI CARDS ───────────────────────────────────── -->
    <div class="analytics-kpi-grid">

        <div class="kpi-card accent-teal">
            <div class="kpi-icon">💰</div>
            <div class="kpi-label">Revenus totaux</div>
            <div class="kpi-value"><?= number_format($kpi['total_revenue'], 0, ',', ' ') ?> <small style="font-size:1rem; font-weight:500;">MAD</small></div>
            <div class="kpi-sub">
                <span class="up">↑</span>
                <?= number_format($kpi['revenue_period'], 0, ',', ' ') ?> MAD sur <?= $period ?> mois
            </div>
        </div>

        <div class="kpi-card accent-green">
            <div class="kpi-icon">📅</div>
            <div class="kpi-label">Réservations totales</div>
            <div class="kpi-value"><?= number_format($kpi['total_bookings']) ?></div>
            <div class="kpi-sub">
                <span class="up">✅ <?= $kpi['accepted'] ?></span> acceptées &nbsp;·&nbsp;
                <span class="neutral">⏳ <?= $kpi['pending'] ?></span> en attente
            </div>
        </div>

        <div class="kpi-card accent-violet">
            <div class="kpi-icon">👥</div>
            <div class="kpi-label">Utilisateurs</div>
            <div class="kpi-value"><?= number_format($kpi['total_users']) ?></div>
            <div class="kpi-sub">
                <span class="up">+<?= $kpi['new_users'] ?></span> nouveaux (<?= $period ?> mois)
            </div>
        </div>

        <div class="kpi-card accent-amber">
            <div class="kpi-icon">📊</div>
            <div class="kpi-label">Panier moyen</div>
            <div class="kpi-value"><?= number_format($kpi['avg_booking'], 0, ',', ' ') ?> <small style="font-size:1rem; font-weight:500;">MAD</small></div>
            <div class="kpi-sub">
                Par réservation confirmée
            </div>
        </div>

        <div class="kpi-card accent-surf">
            <div class="kpi-icon">🏄</div>
            <div class="kpi-label">Cours de surf</div>
            <div class="kpi-value"><?= number_format($kpi['surf_bookings']) ?></div>
            <div class="kpi-sub">réservations surf</div>
        </div>

        <div class="kpi-card accent-teal">
            <div class="kpi-icon">🏨</div>
            <div class="kpi-label">Hôtels</div>
            <div class="kpi-value"><?= number_format($kpi['hotel_bookings']) ?></div>
            <div class="kpi-sub">réservations hôtel</div>
        </div>

        <div class="kpi-card accent-rose">
            <div class="kpi-icon">⭐</div>
            <div class="kpi-label">Note moyenne</div>
            <div class="kpi-value"><?= number_format($kpi['avg_rating'], 1) ?><small style="font-size:1rem;">/5</small></div>
            <div class="kpi-sub"><?= number_format($kpi['total_reviews']) ?> avis au total</div>
        </div>

        <div class="kpi-card accent-green">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-label">Taux de conversion</div>
            <div class="kpi-value"><?= $conversion ?>%</div>
            <div class="kpi-sub">réservations acceptées / total</div>
        </div>

    </div>

    <!-- ── CHARTS ROW 1: Revenue line + Donut status ────── -->
    <div class="charts-grid">

        <!-- Revenue line chart -->
        <div class="card">
            <div class="card-header">
                <h3>📈 Évolution des revenus</h3>
                <span style="font-size:0.8rem; color:var(--text-light);">
                    <?= $period ?> derniers mois
                </span>
            </div>
            <canvas id="revenueChart" height="180"></canvas>
        </div>

        <!-- Status donut -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Statuts des réservations</h3>
            </div>
            <canvas id="statusChart" height="220"></canvas>
            <div style="display:flex; justify-content:center; gap:1.2rem; margin-top:0.8rem; flex-wrap:wrap;">
                <?php
                $statusColors = ['accepted'=>'#10b981','pending'=>'#f59e0b','rejected'=>'#ef4444'];
                $statusLabels = ['accepted'=>'Confirmées','pending'=>'En attente','rejected'=>'Refusées'];
                foreach ($by_status as $s):
                    $col = $statusColors[$s['status']] ?? '#6b7280';
                ?>
                <div style="display:flex;align-items:center;gap:0.4rem;font-size:0.8rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:<?= $col ?>;"></span>
                    <?= $statusLabels[$s['status']] ?? $s['status'] ?>
                    <strong><?= $s['cnt'] ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ── CHARTS ROW 2: Users + Type + Conversion ──────── -->
    <div class="charts-grid-3">

        <!-- Users bar chart -->
        <div class="card">
            <div class="card-header">
                <h3>👥 Nouveaux utilisateurs</h3>
            </div>
            <canvas id="usersChart" height="200"></canvas>
        </div>

        <!-- Type doughnut (surf vs hotel) -->
        <div class="card">
            <div class="card-header">
                <h3>🔀 Répartition par type</h3>
            </div>
            <canvas id="typeChart" height="160"></canvas>
            <div style="display:flex;justify-content:center;gap:1.5rem;margin-top:0.8rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;">
                    <span style="width:12px;height:12px;border-radius:3px;background:#0ea5e9;"></span>
                    🏄 Surf
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;">
                    <span style="width:12px;height:12px;border-radius:3px;background:#8b5cf6;"></span>
                    🏨 Hôtel
                </div>
            </div>
        </div>

        <!-- Conversion + rating -->
        <div class="card">
            <div class="card-header">
                <h3>🎯 Conversion & Avis</h3>
            </div>
            <div class="conversion-wrap">
                <div class="conversion-ring">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--border,#e5e7eb)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#10b981" stroke-width="10"
                            stroke-dasharray="<?= round($conversion * 3.14159, 1) ?> 314.159"
                            stroke-linecap="round"/>
                    </svg>
                    <div class="ring-text">
                        <div class="ring-pct"><?= $conversion ?>%</div>
                        <div class="ring-label">conversion</div>
                    </div>
                </div>
            </div>

            <!-- Ratings distribution -->
            <div style="padding: 0 0.5rem;">
                <?php
                $ratings_map = [];
                foreach ($ratings_dist as $r) $ratings_map[$r['rating']] = $r['cnt'];
                $max_cnt = max(array_values($ratings_map) ?: [1]);
                for ($star = 5; $star >= 1; $star--):
                    $cnt = $ratings_map[$star] ?? 0;
                    $pct = $max_cnt > 0 ? round(($cnt / $max_cnt) * 100) : 0;
                    $colors = [5=>'#10b981',4=>'#34d399',3=>'#f59e0b',2=>'#fb923c',1=>'#ef4444'];
                ?>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-header">
                        <span><?= $star ?>★</span>
                        <span style="color:var(--text-light)"><?= $cnt ?> avis</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill"
                             style="width:<?= $pct ?>%; background:<?= $colors[$star] ?>;">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>

    <!-- ── TOP PERFORMERS ───────────────────────────────── -->
    <div class="charts-grid">

        <!-- Top surf courses -->
        <div class="card">
            <div class="card-header">
                <h3>🏄 Top cours de surf</h3>
                <a href="surf-courses/courses.php" style="font-size:0.85rem; color:var(--primary); font-weight:600;">Gérer →</a>
            </div>
            <?php if (empty($top_surf)): ?>
                <p style="text-align:center;color:var(--text-light);padding:2rem;">Aucun cours enregistré</p>
            <?php else: ?>
            <table class="top-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cours</th>
                        <th>Niveau</th>
                        <th>Réservations</th>
                        <th>Revenus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_surf as $i => $s): ?>
                    <tr>
                        <td>
                            <span class="rank-badge <?= ['rank-1','rank-2','rank-3','rank-other','rank-other'][$i] ?? 'rank-other' ?>">
                                <?= $i + 1 ?>
                            </span>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($s['title']) ?></td>
                        <td>
                            <?php $lvls = ['beginner'=>'🟢 Débutant','intermediate'=>'🟡 Inter.','advanced'=>'🔴 Avancé']; ?>
                            <span style="font-size:0.78rem;"><?= $lvls[$s['level']] ?? $s['level'] ?></span>
                        </td>
                        <td>
                            <span style="font-weight:700; color:var(--primary);"><?= $s['bookings'] ?></span>
                        </td>
                        <td style="font-weight:600; color:#10b981;">
                            <?= number_format($s['revenue'], 0, ',', ' ') ?> MAD
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Top hotels -->
        <div class="card">
            <div class="card-header">
                <h3>🏨 Top hôtels</h3>
                <a href="hotels/hotels.php" style="font-size:0.85rem; color:var(--primary); font-weight:600;">Gérer →</a>
            </div>
            <?php if (empty($top_hotels)): ?>
                <p style="text-align:center;color:var(--text-light);padding:2rem;">Aucun hôtel enregistré</p>
            <?php else: ?>
            <table class="top-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hôtel</th>
                        <th>Ville</th>
                        <th>Réservations</th>
                        <th>Revenus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_hotels as $i => $h): ?>
                    <tr>
                        <td>
                            <span class="rank-badge <?= ['rank-1','rank-2','rank-3','rank-other','rank-other'][$i] ?? 'rank-other' ?>">
                                <?= $i + 1 ?>
                            </span>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($h['name']) ?></td>
                        <td style="color:var(--text-light); font-size:0.83rem;">
                            <?= htmlspecialchars($h['location'] ?? '—') ?>
                        </td>
                        <td>
                            <span style="font-weight:700; color:var(--primary);"><?= $h['bookings'] ?></span>
                        </td>
                        <td style="font-weight:600; color:#10b981;">
                            <?= number_format($h['revenue'], 0, ',', ' ') ?> MAD
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

</div><!-- /.admin-body -->
</main>
</div><!-- /.admin-shell -->

<script src="../assets/js/main.js"></script>
<script>
// ── Data from PHP ──────────────────────────────────────────
const months       = <?= json_encode(array_column($monthly, 'month')) ?>;
const revenueData  = <?= json_encode(array_map('floatval', array_column($monthly, 'revenue'))) ?>;
const bookingData  = <?= json_encode(array_map('intval',   array_column($monthly, 'bookings'))) ?>;

const userMonths   = <?= json_encode(array_column($monthly_users, 'month')) ?>;
const userData     = <?= json_encode(array_map('intval', array_column($monthly_users, 'cnt'))) ?>;

const statusLabels = <?= json_encode(array_column($by_status, 'status')) ?>;
const statusCounts = <?= json_encode(array_map('intval', array_column($by_status, 'cnt'))) ?>;

const typeLabels   = <?= json_encode(array_column($by_type, 'type')) ?>;
const typeCounts   = <?= json_encode(array_map('intval', array_column($by_type, 'cnt'))) ?>;

// ── Common defaults ────────────────────────────────────────
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = '#9ca3af';

// ── Revenue line chart ─────────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Revenus (MAD)',
                data: revenueData,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#0ea5e9',
                pointRadius: 4,
                tension: 0.4,
                fill: true,
                yAxisID: 'y',
            },
            {
                label: 'Réservations',
                data: bookingData,
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [5, 3],
                pointBackgroundColor: '#10b981',
                pointRadius: 3,
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: true,
                labels: { boxWidth: 12, font: { size: 12 } }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ctx.parsed.y.toLocaleString('fr-FR') + ' MAD'
                        : ctx.parsed.y + ' rés.'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { callback: v => v.toLocaleString('fr-FR') }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { stepSize: 1 }
            },
            x: { grid: { display: false } }
        }
    }
});

// ── Status donut ───────────────────────────────────────────
const statusColorMap = {
    accepted: '#10b981',
    pending:  '#f59e0b',
    rejected: '#ef4444',
};
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels.map(s => ({accepted:'Confirmées',pending:'En attente',rejected:'Refusées'}[s] || s)),
        datasets: [{
            data: statusCounts,
            backgroundColor: statusLabels.map(s => statusColorMap[s] || '#6b7280'),
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` }
            }
        }
    }
});

// ── Users bar chart ────────────────────────────────────────
new Chart(document.getElementById('usersChart'), {
    type: 'bar',
    data: {
        labels: userMonths,
        datasets: [{
            label: 'Nouveaux utilisateurs',
            data: userData,
            backgroundColor: 'rgba(139,92,246,0.75)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

// ── Type polar chart ───────────────────────────────────────
new Chart(document.getElementById('typeChart'), {
    type: 'polarArea',
    data: {
        labels: typeLabels.map(t => t === 'surf' ? '🏄 Surf' : '🏨 Hôtel'),
        datasets: [{
            data: typeCounts,
            backgroundColor: ['rgba(14,165,233,0.7)', 'rgba(139,92,246,0.7)'],
            borderColor: ['#0ea5e9', '#8b5cf6'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            r: { ticks: { display: false }, grid: { color: 'rgba(0,0,0,0.06)' } }
        }
    }
});
</script>

</body>
</html>