<?php
session_start();
require_once '../includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: ../profile/profile.php#bookings');
    exit;
}

// ─── Fetch booking ────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT b.*,
        COALESCE(h.name, s.title) AS item_name
    FROM bookings b
    LEFT JOIN hotels       h ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf'  AND b.reference_id = s.id
    WHERE b.id = :id AND b.user_id = :uid
");
$stmt->execute([':id' => $booking_id, ':uid' => $user_id]);
$booking = $stmt->fetch();

// ─── Security checks ──────────────────────────────────────
if (!$booking) {
    // Réservation introuvable ou n'appartient pas à l'utilisateur
    header('Location: ../profile/profile.php#bookings');
    exit;
}

if ($booking['status'] === 'rejected') {
    // Déjà annulée
    header('Location: ../profile/profile.php?cancel_error=already#bookings');
    exit;
}

if ($booking['status'] === 'accepted') {
    // Vérifier si check_in est dans moins de 24h
    $check_in   = new DateTime($booking['check_in']);
    $now        = new DateTime();
    $diff_hours = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;

    if ($diff_hours < 24) {
        header('Location: ../profile/profile.php?cancel_error=too_late#bookings');
        exit;
    }
}

// ─── Handle confirmation ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {

    try {
        $pdo->beginTransaction();

        // 1. Update booking status
        $pdo->prepare("
            UPDATE bookings SET status = 'rejected' WHERE id = :id AND user_id = :uid
        ")->execute([':id' => $booking_id, ':uid' => $user_id]);

        // 2. Remove loyalty points if booking was accepted
        if ($booking['status'] === 'accepted') {
            $points_to_remove = (int)floor($booking['total_price'] / 100);
            if ($points_to_remove > 0) {
                $pdo->prepare("
                    UPDATE users
                    SET loyalty_points = GREATEST(0, loyalty_points - :pts)
                    WHERE id = :uid
                ")->execute([':pts' => $points_to_remove, ':uid' => $user_id]);

                $pdo->prepare("
                    INSERT INTO loyalty_history (user_id, points, reason, created_at)
                    VALUES (:uid, :pts, :reason, NOW())
                ")->execute([
                    ':uid'    => $user_id,
                    ':pts'    => -$points_to_remove,
                    ':reason' => "Annulation réservation #$booking_id",
                ]);
            }
        }

        // 3. Notification
        $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, is_read, created_at)
            VALUES (:uid, :title, :message, 0, NOW())
        ")->execute([
            ':uid'     => $user_id,
            ':title'   => '❌ Réservation annulée',
            ':message' => "Votre réservation #$booking_id pour « {$booking['item_name']} » a été annulée.",
        ]);

        $pdo->commit();

        header('Location: ../profile/profile.php?cancelled=1#bookings');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Une erreur est survenue. Veuillez réessayer.";
    }
}

// ─── Helpers ──────────────────────────────────────────────
$nights = 0;
if (!empty($booking['check_in']) && !empty($booking['check_out'])) {
    $nights = max(0, (new DateTime($booking['check_in']))->diff(new DateTime($booking['check_out']))->days);
}

$status_labels = [
    'pending'  => ['label' => 'En attente',  'color' => '#f59e0b'],
    'accepted' => ['label' => 'Confirmée',   'color' => '#0e7c6b'],
    'rejected' => ['label' => 'Annulée',     'color' => '#d64545'],
];
$sc = $status_labels[$booking['status']] ?? $status_labels['pending'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuler la réservation — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .cancel-wrapper {
            width: 100%;
            max-width: 520px;
        }

        .cancel-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: 0 6px 32px rgba(10,22,40,0.10);
            overflow: hidden;
        }

        /* Header */
        .cancel-header {
            background: linear-gradient(135deg, #0a1628, #0d2240);
            padding: 1.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cancel-header-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: rgba(214,69,69,0.2);
            border: 1px solid rgba(214,69,69,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .cancel-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 .2rem;
            letter-spacing: -0.02em;
        }

        .cancel-header p {
            font-size: .78rem;
            color: rgba(255,255,255,0.5);
            margin: 0;
        }

        /* Body */
        .cancel-body { padding: 1.5rem; }

        /* Booking preview */
        .booking-preview {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid rgba(10,22,40,0.07);
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .booking-preview img {
            width: 72px; height: 60px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
            background: #e8f0f8;
        }

        .booking-preview-info { flex: 1; min-width: 0; }

        .booking-preview-name {
            font-family: 'Syne', sans-serif;
            font-size: .95rem;
            font-weight: 700;
            color: #0a1628;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: .3rem;
        }

        .booking-preview-meta {
            font-size: .78rem;
            color: #7a90a4;
        }

        .booking-preview-price {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            color: #0e7c6b;
            font-size: 1rem;
            flex-shrink: 0;
        }

        /* Warning box */
        .warning-box {
            background: #fff8f0;
            border: 1px solid rgba(200,150,90,0.25);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: .75rem;
            align-items: flex-start;
        }

        .warning-box-icon { font-size: 1.2rem; flex-shrink: 0; }

        .warning-box-text {
            font-size: .85rem;
            color: #7a4e10;
            line-height: 1.6;
        }

        .warning-box-text strong { color: #5a3608; }

        /* Details list */
        .details-list {
            border: 1px solid rgba(10,22,40,0.07);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .75rem 1rem;
            font-size: .85rem;
            border-bottom: 1px solid rgba(10,22,40,0.06);
        }

        .details-row:last-child { border-bottom: none; }

        .details-row span:first-child { color: #7a90a4; }
        .details-row span:last-child  { font-weight: 600; color: #0a1628; }

        /* Buttons */
        .cancel-actions {
            display: flex;
            gap: .75rem;
        }

        .btn-back {
            flex: 1;
            height: 46px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(10,22,40,0.13);
            background: #fff;
            color: #3d5166;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .22s ease;
        }

        .btn-back:hover { background: #f8fafc; }

        .btn-confirm-cancel {
            flex: 1;
            height: 46px;
            display: flex; align-items: center; justify-content: center;
            gap: .5rem;
            border-radius: 10px;
            border: none;
            background: #d64545;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .22s ease;
            box-shadow: 0 4px 14px rgba(214,69,69,0.3);
        }

        .btn-confirm-cancel:hover {
            background: #c03030;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(214,69,69,0.38);
        }

        /* Error alert */
        .alert-error {
            background: #fff2f2;
            border: 1px solid rgba(214,69,69,0.2);
            color: #932020;
            border-radius: 10px;
            padding: .85rem 1rem;
            font-size: .875rem;
            margin-bottom: 1.25rem;
        }

        /* Back link */
        .back-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: #7a90a4;
            text-decoration: none;
            font-size: .85rem;
            margin-bottom: 1.25rem;
            transition: color .2s;
        }

        .back-link:hover { color: #0a1628; }
    </style>
</head>
<body>

<div class="cancel-wrapper">

    <a href="../profile/profile.php#bookings" class="back-link">
        ← Retour à mes réservations
    </a>

    <div class="cancel-card">

        <!-- Header -->
        <div class="cancel-header">
            <div class="cancel-header-icon">🗑️</div>
            <div>
                <h1>Annuler la réservation</h1>
                <p>Réservation #<?= $booking_id ?></p>
            </div>
        </div>

        <!-- Body -->
        <div class="cancel-body">

            <?php if (!empty($error)): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Booking preview -->
            <div class="booking-preview">
                <img
                    src="../uploads/<?= $booking['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($booking['item_image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($booking['item_name'] ?? '') ?>"
                >
                <div class="booking-preview-info">
                    <div class="booking-preview-name">
                        <?= htmlspecialchars($booking['item_name'] ?? 'N/A') ?>
                    </div>
                    <div class="booking-preview-meta">
                        <?= $booking['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                        <?php if (!empty($booking['check_in'])): ?>
                         · 📅 <?= date('d/m/Y', strtotime($booking['check_in'])) ?>
                        <?php endif; ?>
                        <?php if ($nights > 0): ?>
                         · <?= $nights ?> nuit<?= $nights > 1 ? 's' : '' ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="booking-preview-price">
                    <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
                </div>
            </div>

            <!-- Warning -->
            <div class="warning-box">
                <div class="warning-box-icon">⚠️</div>
                <div class="warning-box-text">
                    <?php if ($booking['status'] === 'accepted'): ?>
                        <strong>Réservation confirmée.</strong> En annulant,
                        <?= (int)floor($booking['total_price'] / 100) ?> points fidélité seront déduits de votre compte.
                    <?php else: ?>
                        <strong>Êtes-vous sûr ?</strong> Cette action est irréversible.
                        Votre réservation sera définitivement annulée.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details -->
            <div class="details-list">
                <div class="details-row">
                    <span>Réservation</span>
                    <span>#<?= $booking_id ?></span>
                </div>
                <div class="details-row">
                    <span>Statut actuel</span>
                    <span style="color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
                </div>
                <?php if (!empty($booking['check_in'])): ?>
                <div class="details-row">
                    <span><?= $booking['type'] === 'surf' ? 'Date du cours' : 'Check-in' ?></span>
                    <span><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($booking['type'] === 'hotel' && !empty($booking['check_out'])): ?>
                <div class="details-row">
                    <span>Check-out</span>
                    <span><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="details-row">
                    <span>Total payé</span>
                    <span style="color:#0e7c6b;"><?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD</span>
                </div>
            </div>

            <!-- Actions -->
            <form method="POST">
                <div class="cancel-actions">
                    <a href="../profile/profile.php#bookings" class="btn-back">
                        ← Garder la réservation
                    </a>
                    <button type="submit" name="confirm_cancel" class="btn-confirm-cancel">
                        🗑️ Confirmer l'annulation
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>