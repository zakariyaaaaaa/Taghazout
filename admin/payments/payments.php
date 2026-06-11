<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit;
}

// ✅ جيب payments من DB مع user_name و booking info
$payments = $pdo->query("
    SELECT p.*,
           u.name AS customer,
           b.check_in, b.check_out, b.type AS booking_type
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN bookings b ON p.booking_id = b.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Stats حقيقية
$totals = [
    'total'   => $pdo->query("SELECT SUM(amount) FROM payments WHERE status='paid'")->fetchColumn() ?? 0,
    'pending' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(),
    'failed'  => $pdo->query("SELECT COUNT(*) FROM payments WHERE status='failed'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.pay-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; margin-bottom:2rem; }
.pay-stat {
    background:var(--bg-card); border:1px solid var(--border-subtle);
    border-radius:var(--radius-lg); padding:1.4rem 1.6rem;
    display:flex; align-items:center; gap:1.1rem;
}
.pay-stat-icon {
    width:46px; height:46px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0;
}
.pay-stat-icon.teal   { background:rgba(14,165,233,.1); color:var(--ocean-teal); }
.pay-stat-icon.yellow { background:rgba(245,158,11,.1); color:#f59e0b; }
.pay-stat-icon.red    { background:rgba(239,68,68,.1);  color:#ef4444; }
.pay-stat-val { font-size:1.55rem; font-weight:700; color:var(--text-primary); font-family:'DM Mono',monospace; line-height:1; }
.pay-stat-lbl { font-size:.78rem; color:var(--text-muted); margin-top:3px; }

.filter-bar {
    display:flex; gap:.8rem; flex-wrap:wrap; align-items:center;
    background:var(--bg-card); border:1px solid var(--border-subtle);
    border-radius:var(--radius-lg); padding:1rem 1.2rem; margin-bottom:1.4rem;
}
.filter-bar input,
.filter-bar select {
    background:var(--bg-surface); border:1px solid var(--border-mid);
    border-radius:8px; color:var(--text-primary);
    padding:8px 13px; font-family:'DM Sans',sans-serif;
    font-size:.85rem; outline:none; transition:border .2s;
}
.filter-bar input { flex:1; min-width:200px; }
.filter-bar input:focus,
.filter-bar select:focus { border-color:var(--ocean-teal); }

.badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 11px; border-radius:50px; font-size:.75rem; font-weight:600;
}
.badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
.badge.paid    { background:rgba(16,185,129,.1); color:#10b981; }
.badge.pending { background:rgba(245,158,11,.1); color:#f59e0b; }
.badge.failed  { background:rgba(239,68,68,.1);  color:#ef4444; }

.method-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.78rem; color:var(--text-muted);
    background:var(--bg-surface); padding:3px 9px;
    border-radius:6px; border:1px solid var(--border-subtle);
}

.pagination { display:flex; gap:6px; justify-content:flex-end; padding-top:1.4rem; }
.page-btn {
    width:34px; height:34px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:.82rem; background:var(--bg-card);
    border:1px solid var(--border-mid); color:var(--text-muted);
    text-decoration:none; cursor:pointer;
}
.page-btn.active { border-color:var(--ocean-teal); color:var(--ocean-teal); background:var(--color-info-bg); }

@media(max-width:900px) {
    .pay-stats { grid-template-columns:repeat(2,1fr); }
    .admin-sidebar { display:none; }
    .admin-main { margin-left:0; }
}
</style>
</head>
<body>
<div class="admin-shell">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Payments</div>
                <div class="topbar-breadcrumb">Home <span>/</span> Payments</div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">
        <div class="page-header">
            <div>
                <h1>Payments</h1>
                <p>Gérez et suivez tous les paiements</p>
            </div>
            <span style="font-size:.85rem;color:var(--text-muted);"><?= date('d/m/Y') ?></span>
        </div>

        <!-- Stats -->
        <div class="pay-stats">
            <div class="pay-stat">
                <div class="pay-stat-icon teal">💰</div>
                <div>
                    <div class="pay-stat-val"><?= number_format($totals['total'], 2) ?></div>
                    <div class="pay-stat-lbl">Total collecté (MAD)</div>
                </div>
            </div>
            <div class="pay-stat">
                <div class="pay-stat-icon yellow">⏳</div>
                <div>
                    <div class="pay-stat-val"><?= $totals['pending'] ?></div>
                    <div class="pay-stat-lbl">En attente</div>
                </div>
            </div>
            <div class="pay-stat">
                <div class="pay-stat-icon red">✕</div>
                <div>
                    <div class="pay-stat-val"><?= $totals['failed'] ?></div>
                    <div class="pay-stat-lbl">Échoués</div>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <input type="text" placeholder="🔍 Rechercher ID, client..." id="searchInput" oninput="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">Tous les statuts</option>
                <option value="paid">Payé</option>
                <option value="pending">En attente</option>
                <option value="failed">Échoué</option>
            </select>
            <select id="methodFilter" onchange="filterTable()">
                <option value="">Tous les moyens</option>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
                <option value="cash">Cash</option>
            </select>
        </div>

        <!-- Table -->
        <div class="card" style="padding:0;overflow:hidden;">
            <div class="admin-table-wrap">
                <table class="admin-table" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Client</th>
                            <th>Booking</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);">
                                Aucun paiement trouvé
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td style="font-family:'DM Mono',monospace;color:var(--ocean-teal);font-weight:600;">
                                #<?= $p['id'] ?>
                            </td>
                            <td style="font-weight:500;">
                                <?= htmlspecialchars($p['customer'] ?? '—') ?>
                            </td>
                            <td style="font-family:'DM Mono',monospace;color:var(--text-muted);font-size:.82rem;">
                                #<?= $p['booking_id'] ?>
                                <?php if ($p['booking_type']): ?>
                                <span style="font-size:.72rem;margin-left:4px;">
                                    <?= $p['booking_type'] === 'hotel' ? '🏨' : '🏄' ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-weight:700;color:var(--ocean-teal);">
                                <?= number_format($p['amount'], 2) ?> MAD
                            </td>
                            <td>
                                <span class="method-badge">
                                    <?php
                                    $icons = ['stripe'=>'💳','paypal'=>'🅿️','cash'=>'💵'];
                                    echo ($icons[$p['method']] ?? '💳') . ' ' . ucfirst($p['method']);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $p['status'] ?>">
                                    <?php
                                    $labels = ['paid'=>'✅ Payé','pending'=>'⏳ En attente','failed'=>'❌ Échoué'];
                                    echo $labels[$p['status']] ?? ucfirst($p['status']);
                                    ?>
                                </span>
                            </td>
                            <td style="font-family:'DM Mono',monospace;color:var(--text-muted);font-size:.78rem;">
                                <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-size:.75rem;color:var(--text-muted);">
                                <?= $p['transaction_id'] ? htmlspecialchars(substr($p['transaction_id'], 0, 16)) . '...' : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.4rem;border-top:1px solid var(--border-subtle);font-size:.82rem;color:var(--text-muted);">
                <span>Total: <?= count($payments) ?> paiements</span>
                <div class="pagination">
                    <a href="#" class="page-btn">‹</a>
                    <a href="#" class="page-btn active">1</a>
                    <a href="#" class="page-btn">›</a>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
<script>
function filterTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const method = document.getElementById('methodFilter').value.toLowerCase();

    document.querySelectorAll('#paymentsTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        const matchQ      = text.includes(q);
        const matchStatus = !status || text.includes(status);
        const matchMethod = !method || text.includes(method);
        row.style.display = (matchQ && matchStatus && matchMethod) ? '' : 'none';
    });
}
</script>
</body>
</html>