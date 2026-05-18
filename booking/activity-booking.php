<?php
session_start();
require_once '../includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// ─── Only POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../activities.php');
    exit;
}

// ─── Get data ─────────────────────────────────────────────
$user_id      = (int)$_SESSION['user_id'];
$reference_id = isset($_POST['reference_id']) ? (int)$_POST['reference_id'] : 0;
$check_in     = isset($_POST['check_in'])     ? trim($_POST['check_in'])     : '';
$guests       = isset($_POST['guests'])       ? (int)$_POST['guests']       : 1;
$check_out    = $check_in; // activity = même jour

// ─── Validation ───────────────────────────────────────────
$errors = [];

if (!$reference_id)  $errors[] = "Référence invalide.";
if (empty($check_in)) $errors[] = "La date est requise.";
if ($guests < 1)      $errors[] = "Nombre de participants invalide.";

if (!empty($check_in)) {
    $d1    = new DateTime($check_in);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($d1 < $today) {
        $errors[] = "La date ne peut pas être dans le passé.";
    }
}

// ─── Fetch activity & price ───────────────────────────────
$price = 0;
if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = :id");
    $stmt->execute([':id' => $reference_id]);
    $activity = $stmt->fetch();

    if (!$activity) {
        $errors[] = "Activité introuvable.";
    } else {
        // prix × personnes
        $price = $activity['price'] * $guests;
    }
}

// ─── Check double booking ─────────────────────────────────
if (empty($errors)) {
    $chk = $pdo->prepare("
        SELECT id FROM bookings
        WHERE user_id      = :uid
          AND type         = 'activity'
          AND reference_id = :rid
          AND check_in     = :check_in
          AND status      != 'rejected'
    ");
    $chk->execute([
        ':uid'      => $user_id,
        ':rid'      => $reference_id,
        ':check_in' => $check_in,
    ]);

    if ($chk->fetch()) {
        $errors[] = "Vous avez déjà réservé cette activité pour cette date.";
    }
}

// ─── Insert booking ───────────────────────────────────────
if (empty($errors)) {
    $pdo->prepare("
        INSERT INTO bookings
            (user_id, type, reference_id, check_in, check_out, guests, total_price, status, created_at)
        VALUES
            (:uid, 'activity', :rid, :check_in, :check_out, :guests, :price, 'pending', NOW())
    ")->execute([
        ':uid'       => $user_id,
        ':rid'       => $reference_id,
        ':check_in'  => $check_in,
        ':check_out' => $check_out,
        ':guests'    => $guests,
        ':price'     => $price,
    ]);

    $booking_id = (int)$pdo->lastInsertId();

    // ─── Loyalty points ───────────────────────────────────
    $points = (int)floor($price / 100);
    if ($points > 0) {
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + :pts WHERE id = :uid")
            ->execute([':pts' => $points, ':uid' => $user_id]);

        $pdo->prepare("
            INSERT INTO loyalty_history (user_id, points, reason, created_at)
            VALUES (:uid, :pts, :reason, NOW())
        ")->execute([
            ':uid'    => $user_id,
            ':pts'    => $points,
            ':reason' => "Réservation activité #$booking_id",
        ]);
    }

    // ─── Notification ─────────────────────────────────────
    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (:uid, :title, :message, 0, NOW())
    ")->execute([
        ':uid'     => $user_id,
        ':title'   => '🎯 Réservation activité reçue',
        ':message' => "Votre réservation #$booking_id est en attente de confirmation.",
    ]);

    // ─── Redirect vers booking-details ────────────────────
    header("Location: booking-details.php?id=$booking_id&success=1");
    exit;
}

// ─── Errors → redirect back ───────────────────────────────
$_SESSION['booking_errors'] = $errors;
header("Location: ../activity-details.php?id=$reference_id&booking_error=1");
exit;