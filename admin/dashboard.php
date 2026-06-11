<?php
session_start();
require_once '../includes/config.php';

// ─── Admin check ──────────────────────────────────────────

if(
   !isset($_SESSION['user_id']) ||
   $_SESSION['role'] != 'admin'
){
   header("Location: ../auth/login.php");
   exit;
}

// ─── Stats ────────────────────────────────────────────────
$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'hotels'   => $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn(),
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'accepted'")->fetchColumn() ?? 0,
    'pending'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'reviews'  => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'activities' => $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn(),
    'surf'     => $pdo->query("SELECT COUNT(*) FROM surf_courses")->fetchColumn(),
];

// ─── Recent bookings ──────────────────────────────────────
$recent_bookings = $pdo->query("
    SELECT b.*, u.name AS user_name,
        COALESCE(h.name, s.title) AS item_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN hotels h ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf' AND b.reference_id = s.id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

// ─── Recent users ─────────────────────────────────────────
$recent_users = $pdo->query("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// ─── Revenue by month ─────────────────────────────────────
$monthly = $pdo->query("
    SELECT
        DATE_FORMAT(created_at, '%b') AS month,
        SUM(total_price) AS revenue,
        COUNT(*) AS bookings
    FROM bookings
    WHERE status = 'accepted'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-shell">

<!-- ══════════════════════════════
     SIDEBAR
══════════════════════════════ -->
<?php 
$_SERVER['PHP_SELF'] = '/admin/dashboard.php'; // force active detection
require_once __DIR__ . '/includes/admin-sidebar.php'; 
?>p<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="admin-main">

<div class="admin-topbar">

    <div class="topbar-left">

        <div>

            <div class="topbar-title">
                Dashboard
            </div>

            <div class="topbar-breadcrumb">
                Home
                <span>/</span>
                Dashboard
            </div>

        </div>

    </div>

    <div class="topbar-right">

        <button class="topbar-icon-btn">
            🔔
            <span class="notif-dot"></span>
        </button>

    </div>

</div>

<div class="admin-body">

    <!-- HEADER -->
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Bienvenue, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> 👋</p>
        </div>
        <div style="display:flex; gap:0.8rem; align-items:center;">
            <span style="font-size:0.85rem; color:var(--text-light);">
                <?= date('d/m/Y') ?>
            </span>
            <a href="bookings/bookings.php" class="btn-primary" style="padding:0.6rem 1.2rem; font-size:0.88rem;">
                + Nouvelle réservation
            </a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card teal">
            <div class="stat-icon" style="background:rgba(14,165,233,0.1); color:var(--ocean-teal);">👥</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['users']) ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-delta up">+12%</div>
        </div>
        <div class="stat-card sand">
            <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:#10b981;">💰</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['revenue'], 0, ',', ' ') ?></div>
                <div class="stat-label">Revenus MAD</div>
            </div>
            <div class="stat-delta up">+8%</div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;">📅</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['bookings']) ?></div>
                <div class="stat-label">Réservations</div>
            </div>
            <div class="stat-delta up">+5%</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;">⏳</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <?php if ($stats['pending'] > 0): ?>
            <a href="bookings/bookings.php" style="font-size:0.75rem; color:#ef4444; font-weight:600;">Voir →</a>
            <?php endif; ?>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon" style="background:rgba(14,165,233,0.1); color:var(--ocean-teal);">🏨</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['hotels']) ?></div>
                <div class="stat-label">Hôtels</div>
            </div>
            <a href="hotels/hotels.php" style="font-size:0.75rem; color:var(--primary); font-weight:600;">Gérer →</a>
        </div>
        <div class="stat-card sand">
            <div class="stat-icon" style="background:rgba(139,92,246,0.1); color:#8b5cf6;">⭐</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['reviews']) ?></div>
                <div class="stat-label">Avis</div>
            </div>
            <a href="reviews/reviews.php" style="font-size:0.75rem; color:#8b5cf6; font-weight:600;">Voir →</a>
        </div>
    </div>

    <!-- CHARTS + TABLE -->
    <div class="admin-grid">

        <!-- CHART -->
        <div class="card">
            <div class="card-header">
                <h3>📈 Revenus (6 derniers mois)</h3>
            </div>
            <canvas id="revenueChart" height="200"></canvas>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="card">
            <div class="card-header">
                <h3>⚡ Actions rapides</h3>
            </div>
            <div style="display:flex; flex-direction:column; gap:0.8rem;">
                <a href="hotels/add-hotel.php" class="quick-action">
                    <span>🏨</span> Ajouter un hôtel
                </a>
                <a href="activities/add-activity.php" class="quick-action">
                    <span>🎯</span> Ajouter une activité
                </a>
                <a href="surf-courses/add-course.php" class="quick-action">
                    <span>🏄</span> Ajouter un cours surf
                </a>
                <a href="restaurants/add-restaurant.php" class="quick-action">
                    <span>🍽️</span> Ajouter un restaurant
                </a>
                <a href="bookings/bookings.php?status=pending" class="quick-action" style="border-color:rgba(239,68,68,0.2); color:#ef4444;">
                    <span>⏳</span> Gérer les réservations en attente
                    <?php if ($stats['pending'] > 0): ?>
                    <span style="margin-left:auto; background:#ef4444; color:white; border-radius:20px; padding:0.1rem 0.5rem; font-size:0.72rem;">
                        <?= $stats['pending'] ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

    </div>

    <!-- RECENT BOOKINGS -->
    <div class="card" style="margin-top:1.5rem;">
        <div class="card-header">
            <h3>📅 Réservations récentes</h3>
            <a href="bookings/bookings.php" style="font-size:0.85rem; color:var(--primary); font-weight:600;">Voir tout →</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Hébergement</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_bookings as $b): ?>
                    <tr>
                        <td>#<?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['user_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($b['item_name'] ?? 'N/A') ?></td>
                        <td>
                            <span style="font-size:0.78rem; padding:0.2rem 0.6rem; border-radius:50px; background:var(--color-info-bg); color:var(--primary);">
                                <?= $b['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                            </span>
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-light);">
                            <?= date('d/m', strtotime($b['check_in'])) ?> → <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                        </td>
                        <td style="font-weight:700; color:var(--primary);">
                            <?= number_format($b['total_price'], 0, ',', ' ') ?> MAD
                        </td>
                        <td>
                            <?php
                            $colors = [
                                'pending'  => ['#f59e0b', 'rgba(245,158,11,0.1)'],
                                'accepted' => ['#10b981', 'rgba(16,185,129,0.1)'],
                                'rejected' => ['#ef4444', 'rgba(239,68,68,0.1)'],
                            ];
                            $c = $colors[$b['status']] ?? $colors['pending'];
                            $labels = ['pending' => '⏳ En attente', 'accepted' => '✅ Confirmée', 'rejected' => '❌ Refusée'];
                            ?>
                            <span style="font-size:0.78rem; padding:0.25rem 0.7rem; border-radius:50px; color:<?= $c[0] ?>; background:<?= $c[1] ?>; font-weight:600;">
                                <?= $labels[$b['status']] ?? $b['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="bookings/booking-details.php?id=<?= $b['id'] ?>"
                                style="font-size:0.82rem; color:var(--primary); font-weight:600;">
                                Voir →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECENT USERS -->
    <div class="card" style="margin-top:1.5rem;">
        <div class="card-header">
            <h3>👥 Nouveaux utilisateurs</h3>
            <a href="users/users.php" style="font-size:0.85rem; color:var(--primary); font-weight:600;">Voir tout →</a>
        </div>
        <div style="display:flex; flex-direction:column; gap:0.8rem;">
            <?php foreach ($recent_users as $u): ?>
            <div style="display:flex; align-items:center; gap:1rem; padding:0.8rem; border-radius:var(--radius-sm); background:var(--bg);">
                <div style="width:40px; height:40px; border-radius:50%; background:var(--color-info-bg); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--primary); font-size:1rem; flex-shrink:0;">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600; font-size:0.9rem; color:var(--text);"><?= htmlspecialchars($u['name']) ?></div>
                    <div style="font-size:0.78rem; color:var(--text-light);"><?= htmlspecialchars($u['email']) ?></div>
                </div>
                <div style="font-size:0.75rem; color:var(--text-light);">
                    <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                </div>
                <span style="font-size:0.72rem; padding:0.2rem 0.6rem; border-radius:50px;
                    background:<?= $u['role'] === 'admin' ? 'rgba(139,92,246,0.1)' : 'var(--primary-light)' ?>;
                    color:<?= $u['role'] === 'admin' ? '#8b5cf6' : 'var(--primary)' ?>;">
                    <?= $u['role'] === 'admin' ? '👑 Admin' : '🌊 User' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    </div>

</main>
</div>

<script src="../assets/js/main.js"></script>
<script>
// ── Revenue Chart ──────────────────────────────────────────
const months  = <?= json_encode(array_column($monthly, 'month')) ?>;
const revenue = <?= json_encode(array_map('floatval', array_column($monthly, 'revenue'))) ?>;

const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Revenus (MAD)',
            data: revenue,
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14,165,233,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#0ea5e9',
            pointRadius: 5,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.parsed.y.toLocaleString('fr-FR') + ' MAD'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: {
                    callback: val => val.toLocaleString('fr-FR') + ' MAD'
                }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
<script>
// Notifications badge — poll kol 5s
(function(){
    function pollUnread() {
        fetch('../../user/messages/get-unread.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const badge = document.querySelector('.sb-nav a[href*="messages"] .nav-badge');
                const link  = document.querySelector('.sb-nav a[href*="messages"]');
                if (!link) return;

                let badge2 = link.querySelector('.nav-badge');
                if (!badge2) {
                    badge2 = document.createElement('span');
                    badge2.className = 'nav-badge';
                    link.appendChild(badge2);
                }
                if (data.count > 0) {
                    badge2.textContent = data.count > 99 ? '99+' : data.count;
                    badge2.style.display = 'inline-flex';
                    // Update title
                    document.title = `(${data.count}) ` + document.title.replace(/^\(\d+\) /,'');
                } else {
                    badge2.style.display = 'none';
                    document.title = document.title.replace(/^\(\d+\) /,'');
                }
            })
            .catch(()=>{});
    }
    pollUnread();
    setInterval(pollUnread, 5000);
})();
</script>
</body>
</html>