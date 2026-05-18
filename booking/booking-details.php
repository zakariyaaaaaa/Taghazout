<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: my-bookings.php');
    exit;
}

// ─── Fetch booking ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

// ─── Fetch item details ───────────────────────────────────
if ($booking['type'] === 'hotel') {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
} else {
    $stmt = $pdo->prepare("SELECT * FROM surf_courses WHERE id = :id");
}
$stmt->execute([':id' => $booking['reference_id']]);
$item = $stmt->fetch();

$nights = (new DateTime($booking['check_in']))->diff(new DateTime($booking['check_out']))->days;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation #<?= $booking['id'] ?> — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div style="max-width:700px; margin:8rem auto 4rem; padding:0 1.5rem;">

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
    <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:var(--radius-sm); padding:1rem 1.5rem; margin-bottom:2rem; color:#065f46; text-align:center; font-weight:500;">
        ✅ Réservation effectuée avec succès! En attente de confirmation.
    </div>
    <?php endif; ?>

    <div style="background:var(--card-bg); border-radius:var(--radius); padding:2.5rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,0.06);">

        <!-- PAY BUTTON -->
        <?php if ($booking['status'] === 'pending'): ?>
        <div style="margin-bottom:2rem; text-align:center; padding-bottom:2rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <p style="color:var(--text-light); font-size:0.85rem; margin-bottom:1rem;">
                ⏳ Votre réservation est en attente de paiement
            </p>
            <a href="../payment/checkout.php?booking_id=<?= $booking['id'] ?>"
                style="display:inline-block; padding:1rem 2.5rem; background:var(--primary); color:white; border-radius:50px; font-weight:700; font-size:1rem; box-shadow:0 4px 20px var(--glow); transition:var(--transition);">
                💳 Payer maintenant — <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
            </a>
        </div>
        <?php endif; ?>

        <!-- HEADER -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <div>
                <p style="color:var(--text-light); font-size:0.85rem; margin-bottom:0.3rem;">Réservation</p>
                <h1 style="font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:700; color:var(--text);">
                    #<?= $booking['id'] ?>
                </h1>
            </div>
            <span style="padding:0.5rem 1.2rem; border-radius:50px; font-size:0.85rem; font-weight:600;
                background:<?= $booking['status'] === 'accepted' ? 'rgba(16,185,129,0.1)' : ($booking['status'] === 'rejected' ? 'rgba(239,68,68,0.1)' : 'rgba(14,165,233,0.1)') ?>;
                color:<?= $booking['status'] === 'accepted' ? '#065f46' : ($booking['status'] === 'rejected' ? '#991b1b' : 'var(--primary)') ?>;">
                <?= $booking['status'] === 'accepted' ? '✅ Confirmée' : ($booking['status'] === 'rejected' ? '❌ Refusée' : '⏳ En attente') ?>
            </span>
        </div>

        <!-- ITEM -->
        <div style="display:flex; gap:1.2rem; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <img
                src="../uploads/<?= $booking['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($item['image'] ?? '') ?>"
                onerror="this.src='../assets/images/default.jpg'"
                style="width:90px; height:75px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0;"
            >
            <div>
                <p style="font-size:0.8rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.3rem;">
                    <?= $booking['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf Course' ?>
                </p>
                <h3 style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700; color:var(--text);">
                    <?= htmlspecialchars($item['name'] ?? $item['title'] ?? '') ?>
                </h3>
            </div>
        </div>

        <!-- DETAILS -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:2rem;">
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Check-in</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= date('d/m/Y', strtotime($booking['check_in'])) ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Check-out</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= date('d/m/Y', strtotime($booking['check_out'])) ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Nuits</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $nights ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Personnes</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $booking['guests'] ?>
                </p>
            </div>
        </div>

        <!-- TOTAL -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.2rem 1.5rem; background:var(--primary-light); border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.1); margin-bottom:2rem;">
            <span style="font-weight:600; color:var(--text);">Total</span>
            <span style="font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:700; color:var(--primary);">
                <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
            </span>
        </div>

        <!-- ACTIONS -->
        <div style="display:flex; gap:1rem;">
            <a href="my-bookings.php"
                style="flex:1; padding:0.85rem; background:var(--primary-light); color:var(--primary); border-radius:50px; font-weight:600; text-align:center; font-size:0.9rem;">
                ← Mes réservations
            </a>
            <a href="../index.php"
                style="flex:1; padding:0.85rem; background:var(--primary); color:white; border-radius:50px; font-weight:600; text-align:center; font-size:0.9rem; box-shadow:0 4px 15px var(--glow);">
                🏠 Accueil
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>