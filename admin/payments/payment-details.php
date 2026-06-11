<?php
// payment-details.php - Admin Payment Details
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit;
}

// Mock data – replace with DB query using $_GET['id']
$payment_id = $_GET['id'] ?? 'PAY-00423';

$payment = [
    'id'         => 'PAY-00423',
    'order_id'   => 'ORD-10987',
    'customer'   => 'Youssef El Amrani',
    'email'      => 'youssef@example.com',
    'amount'     => '349.00',
    'currency'   => 'MAD',
    'method'     => 'Credit Card',
    'card_last4' => '4242',
    'status'     => 'completed',
    'date'       => '2026-05-17 14:32:05',
    'gateway'    => 'Stripe',
    'txn_id'     => 'ch_3PxK2LKZ2eZvKYlo',
    'ip'         => '105.158.22.41',
    'notes'      => '',
];

$status_colors = [
    'completed' => ['#10b981', 'rgba(16,185,129,.1)'],
    'pending'   => ['#f59e0b', 'rgba(245,158,11,.1)'],
    'failed'    => ['#ef4444', 'rgba(239,68,68,.1)'],
    'refunded'  => ['#8b5cf6', 'rgba(139,92,246,.1)'],
];
$sc = $status_colors[$payment['status']] ?? $status_colors['pending'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Details – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.details-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; }

.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 11px 0; border-bottom: 1px solid var(--border);
}
.info-row:last-child { border-bottom: none; }
.info-label { font-size: .82rem; color: var(--text-light); }
.info-value { font-size: .88rem; font-family: 'DM Mono', monospace; color: var(--text); font-weight: 500; }
.info-value.accent { color: var(--ocean-teal); }

.amount-display {
    text-align: center; padding: 2rem 0 1.6rem;
    border-bottom: 1px solid var(--border); margin-bottom: 1.2rem;
}
.amount-display .amount-num {
    font-size: 2.6rem; font-weight: 700; font-family: 'DM Mono', monospace;
    color: var(--text); line-height: 1;
}
.amount-display .amount-cur { font-size: 1rem; color: var(--text-light); margin-left: 6px; }

/* Status badge */
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 50px; font-size: .8rem; font-weight: 600;
    border: 1px solid transparent;
}

/* Action buttons */
.action-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 18px; border-radius: 9px; font-size: .875rem;
    font-family: 'DM Sans', sans-serif; font-weight: 600;
    cursor: pointer; transition: all .2s; text-decoration: none; border: none; width: 100%;
}
.action-btn + .action-btn { margin-top: .65rem; }
.action-btn.primary  { background: var(--ocean-teal); color: #fff; }
.action-btn.primary:hover { opacity: .88; }
.action-btn.outline  { background: transparent; border: 1px solid var(--border); color: var(--text); }
.action-btn.outline:hover { border-color: var(--ocean-teal); color: var(--ocean-teal); }
.action-btn.danger   { background: rgba(239,68,68,.07); color: #ef4444; border: 1px solid rgba(239,68,68,.2); }
.action-btn.danger:hover { background: rgba(239,68,68,.14); }

/* Timeline */
.tl-item { display: flex; gap: 14px; padding-bottom: 22px; position: relative; }
.tl-item::before {
    content:''; position:absolute; left:7px; top:18px; bottom:0;
    width:1px; background:var(--border);
}
.tl-item:last-child::before { display:none; }
.tl-dot { width:15px; height:15px; border-radius:50%; flex-shrink:0; margin-top:3px; }
.tl-dot.green  { background:#10b981; }
.tl-dot.yellow { background:#f59e0b; }
.tl-dot.gray   { background:var(--border); }
.tl-title { font-size:.875rem; font-weight:600; color:var(--text); }
.tl-time  { font-size:.75rem; color:var(--text-light); font-family:'DM Mono',monospace; margin-top:3px; }

.mono { font-family: 'DM Mono', monospace; }

@media(max-width:900px){
    .details-grid { grid-template-columns:1fr; }
    .admin-sidebar { display:none; }
    .admin-main { margin-left:0; }
}
</style>
</head>
<body>
<div class="admin-shell">

<!-- ── Sidebar ─────────────────────────────────────────── -->
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
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <a href="../analytics.php"><span class="nav-icon">📈</span><span>Analytics</span></a>

        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>

        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="payments.php" class="active"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
        <a href="../messages/messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
    </nav>

    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>

<!-- ── Main ──────────────────────────────────────────────── -->
<main class="admin-main">

    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Payment Details</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="payments.php" style="color:var(--text-light);text-decoration:none;">Payments</a>
                    <span>/</span> <?= htmlspecialchars($payment['id']) ?>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Payment Details</h1>
                <p style="font-family:'DM Mono',monospace; font-size:.85rem; color:var(--text-light); margin-top:4px;">
                    <?= htmlspecialchars($payment['id']) ?> · <?= $payment['date'] ?>
                </p>
            </div>
            <span class="status-badge"
                  style="color:<?= $sc[0] ?>; background:<?= $sc[1] ?>; border-color:<?= $sc[0] ?>33;">
                <?= ucfirst($payment['status']) ?>
            </span>
        </div>

        <div class="details-grid">

            <!-- LEFT -->
            <div style="display:flex; flex-direction:column; gap:1.4rem;">

                <!-- Transaction Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3>💳 Transaction Summary</h3>
                    </div>
                    <div style="padding:0 1.4rem;">
                        <div class="amount-display">
                            <span class="amount-num"><?= $payment['amount'] ?></span>
                            <span class="amount-cur"><?= $payment['currency'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Order ID</span>
                            <a href="../bookings/booking-details.php?id=<?= $payment['order_id'] ?>"
                               class="info-value accent" style="text-decoration:none;"><?= $payment['order_id'] ?></a>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Méthode de paiement</span>
                            <span class="info-value"><?= $payment['method'] ?> &nbsp;····&nbsp; <?= $payment['card_last4'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gateway</span>
                            <span class="info-value"><?= $payment['gateway'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transaction ID</span>
                            <span class="info-value" style="font-size:.8rem;"><?= $payment['txn_id'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Adresse IP</span>
                            <span class="info-value"><?= $payment['ip'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Customer -->
                <div class="card">
                    <div class="card-header">
                        <h3>👤 Client</h3>
                        <a href="../users/user-details.php?email=<?= urlencode($payment['email']) ?>"
                           style="font-size:.82rem; color:var(--primary); font-weight:600; text-decoration:none;">
                            Voir profil →
                        </a>
                    </div>
                    <div style="padding:0 1.4rem;">
                        <div class="info-row">
                            <span class="info-label">Nom</span>
                            <span class="info-value" style="font-family:'DM Sans',sans-serif; font-weight:600;">
                                <?= htmlspecialchars($payment['customer']) ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($payment['email']) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div style="display:flex; flex-direction:column; gap:1.4rem;">

                <!-- Actions -->
                <div class="card">
                    <div class="card-header"><h3>⚡ Actions</h3></div>
                    <div style="padding:0 1.4rem 1.4rem;">
                        <a href="refunds.php?payment=<?= $payment['id'] ?>" class="action-btn primary">↩ Émettre un remboursement</a>
                        <a href="../bookings/booking-details.php?id=<?= $payment['order_id'] ?>" class="action-btn outline">📅 Voir la réservation</a>
                        <button class="action-btn outline" onclick="window.print()">🖨️ Imprimer le reçu</button>
                        <?php if ($payment['status'] !== 'failed'): ?>
                        <button class="action-btn danger" onclick="if(confirm('Marquer comme échoué ?')) location.href='?id=<?= $payment['id'] ?>&action=mark_failed'">
                            ✕ Marquer comme échoué
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="card">
                    <div class="card-header"><h3>📋 Journal d'activité</h3></div>
                    <div style="padding:1rem 1.4rem 1.4rem;">
                        <div class="tl-item">
                            <div class="tl-dot green"></div>
                            <div>
                                <div class="tl-title">✅ Paiement confirmé</div>
                                <div class="tl-time">2026-05-17 14:32:05</div>
                            </div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-dot yellow"></div>
                            <div>
                                <div class="tl-title">⏳ Paiement initié</div>
                                <div class="tl-time">2026-05-17 14:31:48</div>
                            </div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-dot gray"></div>
                            <div>
                                <div class="tl-title">📋 Réservation créée</div>
                                <div class="tl-time">2026-05-17 14:31:10</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>