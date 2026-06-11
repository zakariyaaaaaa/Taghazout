<?php
session_start();
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$booking_id = (int)($_GET['booking_id'] ?? 0);
$session_id = $_GET['session_id'] ?? '';

if (!$booking_id) {
    header('Location: ../booking/my-bookings.php');
    exit;
}

// ─── Verify with Stripe ───────────────────────────────────
\Stripe\Stripe::setApiKey('sk_test_51TOagtBLRMTX4QBlssAcnT5x5neZvW4G2pjZ8IJ6ncOXdWRhXaOhUM5VV316jXPCldLU8xOlV2c78xoPcrzDfTri00T9nZSBNE');

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status === 'paid') {
        // Update booking status
        $pdo->prepare("UPDATE bookings SET status = 'accepted' WHERE id = :id AND user_id = :uid")
            ->execute([':id' => $booking_id, ':uid' => $_SESSION['user_id']]);

        // Update payment
        $pdo->prepare("
            UPDATE payments SET status = 'paid', transaction_id = :sid
            WHERE booking_id = :bid
        ")->execute([':sid' => $session_id, ':bid' => $booking_id]);

        // Notification
        $pdo->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (:uid, :title, :msg)
        ")->execute([
            ':uid'   => $_SESSION['user_id'],
            ':title' => '✅ Paiement confirmé',
            ':msg'   => "Votre paiement pour la réservation #$booking_id a été confirmé.",
        ]);
    }
} catch (Exception $e) {
    // Session invalide - on continue quand même
}

// ─── Fetch booking ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $booking_id, ':uid' => $_SESSION['user_id']]);
$booking = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div style="max-width:600px; margin:8rem auto 4rem; padding:0 1.5rem; text-align:center;">
    <div style="background:var(--card-bg); border-radius:var(--radius); padding:3rem 2rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,0.06);">

        <div style="width:80px; height:80px; background:rgba(16,185,129,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; font-size:2.5rem;">
            ✅
        </div>

        <h1 style="font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:700; color:var(--text); margin-bottom:0.8rem;">
            Paiement réussi!
        </h1>

        <p style="color:var(--text-light); margin-bottom:2rem; line-height:1.7;">
            Votre réservation <strong>#<?= $booking_id ?></strong> a été confirmée.<br>
            Merci pour votre confiance! 🏄
        </p>

        <?php if ($booking): ?>
        <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:2rem; text-align:left;">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                <span style="color:var(--text-light); font-size:0.88rem;">Check-in</span>
                <span style="font-weight:600; color:var(--text);"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                <span style="color:var(--text-light); font-size:0.88rem;">Check-out</span>
                <span style="font-weight:600; color:var(--text);"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding-top:0.5rem; border-top:1px solid rgba(14,165,233,0.1);">
                <span style="color:var(--text-light); font-size:0.88rem;">Total payé</span>
                <span style="font-family:'Syne',sans-serif; font-weight:700; color:var(--primary);">
                    <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:1rem; justify-content:center;">
            <a href="../booking/my-bookings.php" class="btn-primary">📋 Mes réservations</a>
            <a href="../index.php" style="padding:0.95rem 2rem; background:var(--primary-light); color:var(--primary); border-radius:50px; font-weight:600; font-size:0.95rem;">
                🏠 Accueil
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>