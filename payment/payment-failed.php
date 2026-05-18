<?php
session_start();
require_once '../includes/config.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement échoué — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div style="max-width:600px; margin:8rem auto 4rem; padding:0 1.5rem; text-align:center;">
    <div style="background:var(--card-bg); border-radius:var(--radius); padding:3rem 2rem; box-shadow:var(--card-shadow);">

        <div style="width:80px; height:80px; background:rgba(239,68,68,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; font-size:2.5rem;">
            ❌
        </div>

        <h1 style="font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:700; color:var(--text); margin-bottom:0.8rem;">
            Paiement annulé
        </h1>

        <p style="color:var(--text-light); margin-bottom:2rem; line-height:1.7;">
            Votre paiement a été annulé ou a échoué.<br>
            Votre réservation est toujours en attente.
        </p>

        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <?php if ($booking_id): ?>
            <a href="checkout.php?booking_id=<?= $booking_id ?>" class="btn-primary">
                🔄 Réessayer
            </a>
            <?php endif; ?>
            <a href="../booking/my-bookings.php" style="padding:0.95rem 2rem; background:var(--primary-light); color:var(--primary); border-radius:50px; font-weight:600; font-size:0.95rem;">
                📋 Mes réservations
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>