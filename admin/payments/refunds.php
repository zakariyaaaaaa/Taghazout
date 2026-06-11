<?php
// refunds.php - Admin Refunds
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit;
}

$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'issue_refund') {
        $payment_id = trim($_POST['payment_id'] ?? '');
        $amount     = floatval($_POST['amount'] ?? 0);
        $reason     = trim($_POST['reason'] ?? '');

        if (!$payment_id || $amount <= 0) {
            $error_msg = 'Payment ID et montant valide sont requis.';
        } else {
            // TODO: appeler l'API gateway (Stripe, etc.)
            $success_msg = "Remboursement de {$amount} MAD émis pour le paiement {$payment_id}.";
        }
    }
}

$prefill_payment = htmlspecialchars($_GET['payment'] ?? '');

$refunds = [
  ['id'=>'REF-0091','payment'=>'PAY-00419','customer'=>'Omar Fassi','amount'=>'75.00','reason'=>'Demande client','status'=>'completed','date'=>'2026-05-16 18:15'],
  ['id'=>'REF-0090','payment'=>'PAY-00410','customer'=>'Hind Berrada','amount'=>'200.00','reason'=>'Paiement en double','status'=>'completed','date'=>'2026-05-15 12:44'],
  ['id'=>'REF-0089','payment'=>'PAY-00405','customer'=>'Mehdi Chaoui','amount'=>'50.00','reason'=>'Produit non reçu','status'=>'processing','date'=>'2026-05-14 09:30'],
  ['id'=>'REF-0088','payment'=>'PAY-00399','customer'=>'Sara Moukrim','amount'=>'340.00','reason'=>'Mauvais article','status'=>'completed','date'=>'2026-05-13 16:05'],
  ['id'=>'REF-0087','payment'=>'PAY-00388','customer'=>'Rachid Ousseini','amount'=>'90.00','reason'=>'Changement d\'avis','status'=>'failed','date'=>'2026-05-12 11:20'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Refunds – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.refunds-layout { display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; }

.mini-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-bottom: 1.8rem; }
.mini-stat-card {
    background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 1.2rem 1.4rem;
}
.mini-stat-lbl { font-size:.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.mini-stat-val { font-size:1.4rem; font-weight:700; font-family:'DM Mono',monospace; }

/* Form */
.form-group { margin-bottom: 1.2rem; }
.form-label { display:block; font-size:.8rem; color:var(--text-light); font-weight:600; letter-spacing:.04em; margin-bottom:6px; }
.form-control {
    width:100%; background:var(--bg); border:1px solid var(--border); border-radius:8px;
    color:var(--text); padding:9px 13px; font-family:'DM Sans',sans-serif; font-size:.875rem;
    outline:none; transition:border .2s;
}
.form-control:focus { border-color: var(--ocean-teal); }
textarea.form-control { min-height:88px; resize:vertical; }
select.form-control { cursor:pointer; }
.amount-row { display:grid; grid-template-columns:1fr 110px; gap:8px; }

/* Alert */
.alert {
    padding:12px 16px; border-radius:9px; font-size:.85rem; margin-bottom:1.2rem;
    display:flex; align-items:center; gap:10px; border:1px solid transparent;
}
.alert.success { background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.25); color:#10b981; }
.alert.error   { background:rgba(239,68,68,.08);  border-color:rgba(239,68,68,.25);  color:#ef4444; }

.warning-box {
    background:rgba(245,158,11,.07); border:1px solid rgba(245,158,11,.2);
    border-radius:9px; padding:12px 15px; font-size:.82rem; color:#f59e0b; margin-bottom:1.2rem;
}

/* Badge */
.badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 11px; border-radius:50px; font-size:.75rem; font-weight:600;
}
.badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
.badge.completed  { background:rgba(16,185,129,.1);  color:#10b981; }
.badge.processing { background:rgba(245,158,11,.1);  color:#f59e0b; }
.badge.failed     { background:rgba(239,68,68,.1);   color:#ef4444; }

.submit-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:11px; border-radius:9px; background:#ef4444; color:#fff;
    font-size:.9rem; font-family:'DM Sans',sans-serif; font-weight:700;
    border:none; cursor:pointer; transition:opacity .2s;
}
.submit-btn:hover { opacity:.88; }

.mono { font-family:'DM Mono',monospace; }

@media(max-width:1024px){
    .refunds-layout { grid-template-columns:1fr; }
    .admin-sidebar { display:none; }
    .admin-main { margin-left:0; }
    .mini-stats { grid-template-columns:repeat(2,1fr); }
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
        <a href="payments.php"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
        <a href="../messages/messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
        <a href="refunds.php" class="active"><span class="nav-icon">↩</span><span>Refunds</span></a>
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
                <div class="topbar-title">Remboursements</div>
                <div class="topbar-breadcrumb">Home <span>/</span> Refunds</div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">

        <div class="page-header">
            <div>
                <h1>Remboursements</h1>
                <p>Émettre et gérer les remboursements de paiements</p>
            </div>
            <span style="font-size:.85rem; color:var(--text-light);"><?= date('d/m/Y') ?></span>
        </div>

        <!-- Mini Stats -->
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-lbl">Total remboursé</div>
                <div class="mini-stat-val" style="color:#8b5cf6;">755.00 MAD</div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-lbl">En cours</div>
                <div class="mini-stat-val" style="color:#f59e0b;">1</div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-lbl">Ce mois</div>
                <div class="mini-stat-val" style="color:var(--text);">5</div>
            </div>
        </div>

        <div class="refunds-layout">

            <!-- Form -->
            <div class="card" style="align-self:start;">
                <div class="card-header"><h3>↩ Nouveau remboursement</h3></div>
                <div style="padding:1.4rem;">

                    <?php if ($success_msg): ?>
                    <div class="alert success">✓ <?= htmlspecialchars($success_msg) ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                    <div class="alert error">✕ <?= htmlspecialchars($error_msg) ?></div>
                    <?php endif; ?>

                    <div class="warning-box">
                        ⚠️ Les remboursements sont irréversibles. Vérifiez bien le montant et le paiement avant de valider.
                    </div>

                    <form method="POST" action="refunds.php">
                        <input type="hidden" name="action" value="issue_refund">

                        <div class="form-group">
                            <label class="form-label">Payment ID</label>
                            <input type="text" name="payment_id" class="form-control"
                                   placeholder="PAY-00XXX"
                                   value="<?= $prefill_payment ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Montant (MAD)</label>
                            <div class="amount-row">
                                <input type="number" name="amount" class="form-control"
                                       placeholder="0.00" step="0.01" min="0.01" required>
                                <select name="currency" class="form-control">
                                    <option>MAD</option>
                                    <option>EUR</option>
                                    <option>USD</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Type de remboursement</label>
                            <select name="type" class="form-control">
                                <option value="full">Remboursement total</option>
                                <option value="partial">Remboursement partiel</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Raison</label>
                            <select name="reason" class="form-control">
                                <option value="">Sélectionner une raison...</option>
                                <option>Demande client</option>
                                <option>Paiement en double</option>
                                <option>Produit non reçu</option>
                                <option>Mauvais article expédié</option>
                                <option>Changement d'avis</option>
                                <option>Fraude suspectée</option>
                                <option>Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notes internes (optionnel)</label>
                            <textarea name="notes" class="form-control" placeholder="Notes internes..."></textarea>
                        </div>

                        <button type="submit" class="submit-btn">↩ Émettre le remboursement</button>
                    </form>
                </div>
            </div>

            <!-- History Table -->
            <div class="card" style="padding:0; overflow:hidden; align-self:start;">
                <div class="card-header"><h3>📋 Historique des remboursements</h3></div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Payment</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Raison</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($refunds as $r): ?>
                            <tr>
                                <td class="mono" style="color:#8b5cf6; font-weight:600; font-size:.82rem;"><?= $r['id'] ?></td>
                                <td>
                                    <a href="payment-details.php?id=<?= urlencode($r['payment']) ?>"
                                       style="color:var(--ocean-teal); font-family:'DM Mono',monospace; text-decoration:none; font-size:.82rem; font-weight:500;">
                                        <?= $r['payment'] ?>
                                    </a>
                                </td>
                                <td style="font-weight:500;"><?= htmlspecialchars($r['customer']) ?></td>
                                <td class="mono" style="font-weight:700; color:var(--primary);"><?= $r['amount'] ?> MAD</td>
                                <td style="color:var(--text-light); font-size:.82rem;"><?= htmlspecialchars($r['reason']) ?></td>
                                <td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td class="mono" style="color:var(--text-light); font-size:.78rem;"><?= $r['date'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>