<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ─── Filter ───────────────────────────────────────────────
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type   = isset($_GET['type'])   ? trim($_GET['type'])   : '';

$where  = "WHERE b.user_id = :uid";
$params = [':uid' => $user_id];

if ($status !== '') {
    $where .= " AND b.status = :status";
    $params[':status'] = $status;
}
if ($type !== '') {
    $where .= " AND b.type = :type";
    $params[':type'] = $type;
}

// ─── Fetch bookings ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT b.*,
        COALESCE(h.name, s.title) AS item_name,
        COALESCE(h.image, s.image) AS item_image,
        COALESCE(h.location, 'Taghazout') AS item_location
    FROM bookings b
    LEFT JOIN hotels h      ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf'  AND b.reference_id = s.id
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// ─── Stats ────────────────────────────────────────────────
$stats = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending'  THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(total_price) as total_spent
    FROM bookings WHERE user_id = :uid
");
$stats->execute([':uid' => $user_id]);
$s = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="page-hero-content">
        <p class="eyebrow">📋 Mon espace</p>
        <h1>Mes Réservations</h1>
        <p>Gérez toutes vos réservations en un seul endroit</p>
    </div>
</section>

<div style="max-width:1100px; margin:3rem auto; padding:0 1.5rem 5rem;">

    <!-- STATS -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2.5rem;">
        <?php
        $cards = [
            ['label' => 'Total',     'value' => $s['total'],   'icon' => '📋', 'color' => 'var(--primary)'],
            ['label' => 'En attente','value' => $s['pending'],  'icon' => '⏳', 'color' => '#f59e0b'],
            ['label' => 'Confirmées','value' => $s['accepted'], 'icon' => '✅', 'color' => '#10b981'],
            ['label' => 'Dépensé',   'value' => number_format($s['total_spent'] ?? 0, 0, ',', ' ') . ' MAD', 'icon' => '💰', 'color' => 'var(--primary)'],
        ];
        foreach ($cards as $card):
        ?>
        <div style="background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,0.06); text-align:center;">
            <div style="font-size:1.8rem; margin-bottom:0.5rem;"><?= $card['icon'] ?></div>
            <div style="font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:700; color:<?= $card['color'] ?>;">
                <?= $card['value'] ?>
            </div>
            <div style="font-size:0.82rem; color:var(--text-light); margin-top:0.3rem;"><?= $card['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTERS -->
    <form method="GET" style="display:flex; gap:0.8rem; margin-bottom:2rem; flex-wrap:wrap;">
        <select name="status" onchange="this.form.submit()"
            style="padding:0.65rem 1rem; border:1.5px solid rgba(14,165,233,0.15); border-radius:50px; font-family:'DM Sans',sans-serif; font-size:0.88rem; background:var(--card-bg); color:var(--text); cursor:pointer; outline:none;">
            <option value="">Tous les statuts</option>
            <option value="pending"  <?= $status === 'pending'  ? 'selected' : '' ?>>⏳ En attente</option>
            <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>✅ Confirmées</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>❌ Refusées</option>
        </select>

        <select name="type" onchange="this.form.submit()"
            style="padding:0.65rem 1rem; border:1.5px solid rgba(14,165,233,0.15); border-radius:50px; font-family:'DM Sans',sans-serif; font-size:0.88rem; background:var(--card-bg); color:var(--text); cursor:pointer; outline:none;">
            <option value="">Tous les types</option>
            <option value="hotel" <?= $type === 'hotel' ? 'selected' : '' ?>>🏨 Hôtels</option>
            <option value="surf"  <?= $type === 'surf'  ? 'selected' : '' ?>>🏄 Surf</option>
        </select>

        <?php if ($status || $type): ?>
        <a href="my-bookings.php"
            style="padding:0.65rem 1.2rem; border:1.5px solid rgba(239,68,68,0.3); border-radius:50px; color:#ef4444; font-size:0.88rem; font-weight:500; transition:var(--transition);">
            ✕ Reset
        </a>
        <?php endif; ?>
    </form>

    <!-- BOOKINGS LIST -->
    <?php if (empty($bookings)): ?>
        <div style="text-align:center; padding:5rem 2rem; background:var(--card-bg); border-radius:var(--radius); box-shadow:var(--card-shadow);">
            <div style="font-size:3rem; margin-bottom:1rem;">📋</div>
            <h3 style="font-family:'Syne',sans-serif; color:var(--text); margin-bottom:0.5rem;">Aucune réservation</h3>
            <p style="color:var(--text-light); margin-bottom:1.5rem;">Vous n'avez pas encore effectué de réservation.</p>
            <a href="../hotels.php" class="btn-primary">Explorer les hôtels</a>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($bookings as $b):
                $nights = (new DateTime($b['check_in']))->diff(new DateTime($b['check_out']))->days;
                $statusColor = $b['status'] === 'accepted' ? '#10b981' : ($b['status'] === 'rejected' ? '#ef4444' : '#f59e0b');
                $statusBg    = $b['status'] === 'accepted' ? 'rgba(16,185,129,0.1)' : ($b['status'] === 'rejected' ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)');
                $statusLabel = $b['status'] === 'accepted' ? '✅ Confirmée' : ($b['status'] === 'rejected' ? '❌ Refusée' : '⏳ En attente');
            ?>
            <div style="background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,0.06); display:flex; gap:1.2rem; align-items:center; flex-wrap:wrap;">

                <!-- Image -->
                <img
                    src="../uploads/<?= $b['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($b['item_image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    style="width:90px; height:75px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0;"
                >

                <!-- Info -->
                <div style="flex:1; min-width:200px;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.3rem;">
                        <span style="font-size:0.75rem; background:var(--primary-light); color:var(--primary); padding:0.2rem 0.7rem; border-radius:50px; font-weight:600;">
                            <?= $b['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                        </span>
                        <span style="font-size:0.75rem; color:var(--text-light);">#<?= $b['id'] ?></span>
                    </div>
                    <h3 style="font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--text); margin-bottom:0.3rem;">
                        <?= htmlspecialchars($b['item_name'] ?? 'N/A') ?>
                    </h3>
                    <p style="font-size:0.85rem; color:var(--text-light);">
                        📅 <?= date('d/m/Y', strtotime($b['check_in'])) ?> → <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                        · <?= $nights ?> nuit<?= $nights > 1 ? 's' : '' ?>
                        · <?= $b['guests'] ?> pers.
                    </p>
                </div>

                <!-- Price + Status -->
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:700; color:var(--primary); margin-bottom:0.5rem;">
                        <?= number_format($b['total_price'], 0, ',', ' ') ?> MAD
                    </div>
                    <span style="padding:0.35rem 0.9rem; border-radius:50px; font-size:0.8rem; font-weight:600; background:<?= $statusBg ?>; color:<?= $statusColor ?>;">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <!-- Actions -->
                <div style="display:flex; flex-direction:column; gap:0.5rem; flex-shrink:0;">
                    <a href="booking-details.php?id=<?= $b['id'] ?>"
                        style="padding:0.5rem 1.2rem; background:var(--primary); color:white; border-radius:50px; font-size:0.85rem; font-weight:600; text-align:center; box-shadow:0 4px 12px var(--glow);">
                        Détails →
                    </a>
                    <?php if ($b['status'] === 'pending'): ?>
                    <a href="cancel-booking.php?id=<?= $b['id'] ?>"
                        onclick="return confirm('Annuler cette réservation ?')"
                        style="padding:0.5rem 1.2rem; background:rgba(239,68,68,0.08); color:#ef4444; border-radius:50px; font-size:0.85rem; font-weight:600; text-align:center; border:1px solid rgba(239,68,68,0.2);">
                        Annuler
                    </a>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>