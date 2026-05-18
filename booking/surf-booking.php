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
    header('Location: ../surf-courses.php');
    exit;
}

// ─── Get data ─────────────────────────────────────────────
$user_id      = (int)$_SESSION['user_id'];
$type         = isset($_POST['type'])         ? trim($_POST['type'])         : 'surf';
$reference_id = isset($_POST['reference_id']) ? (int)$_POST['reference_id'] : 0;
$check_in     = isset($_POST['check_in'])     ? trim($_POST['check_in'])     : '';
$check_out    = isset($_POST['check_out'])    ? trim($_POST['check_out'])    : '';
$guests       = isset($_POST['guests'])       ? (int)$_POST['guests']       : 1;

// ✅ FIX 1 — surf: check_out = check_in (même jour)
if ($type === 'surf' && empty($check_out)) {
    $check_out = $check_in;
}

// ─── Validation ───────────────────────────────────────────
$errors = [];

if (!in_array($type, ['hotel', 'surf'])) {
    $errors[] = "Type de réservation invalide.";
}
if (!$reference_id) {
    $errors[] = "Référence invalide.";
}
if (empty($check_in)) {
    $errors[] = "La date est requise.";
}
if (!empty($check_in)) {
    $d1    = new DateTime($check_in);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($d1 < $today) {
        $errors[] = "La date ne peut pas être dans le passé.";
    }

    // ✅ FIX 2 — check_out > check_in uniquement pour les hôtels
    if ($type === 'hotel') {
        if (empty($check_out)) {
            $errors[] = "La date de départ est requise.";
        } else {
            $d2 = new DateTime($check_out);
            if ($d2 <= $d1) {
                $errors[] = "La date de départ doit être après la date d'arrivée.";
            }
        }
    }
}

if ($guests < 1) {
    $errors[] = "Nombre de participants invalide.";
}

// ─── Get price ────────────────────────────────────────────
$price = 0;
if (empty($errors)) {
    $table = $type === 'hotel' ? 'hotels' : 'surf_courses';
    $stmt  = $pdo->prepare("SELECT price FROM $table WHERE id = :id");
    $stmt->execute([':id' => $reference_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $errors[] = "Élément introuvable.";
    } else {
        if ($type === 'hotel') {
            // Hôtel: prix × nuits
            $d1     = new DateTime($check_in);
            $d2     = new DateTime($check_out);
            $nights = max(1, $d2->diff($d1)->days);
            $price  = $item['price'] * $nights;
        } else {
            // Surf: prix × personnes
            $price = $item['price'] * $guests;
        }
    }
}

// ─── Check double booking ─────────────────────────────────
if (empty($errors)) {
    if ($type === 'hotel') {
        $chk = $pdo->prepare("
            SELECT id FROM bookings
            WHERE user_id      = :uid
              AND type         = 'hotel'
              AND reference_id = :rid
              AND status      != 'rejected'
              AND check_in    <= :check_out
              AND check_out   >= :check_in
        ");
        $chk->execute([
            ':uid'       => $user_id,
            ':rid'       => $reference_id,
            ':check_in'  => $check_in,
            ':check_out' => $check_out,
        ]);
    } else {
        $chk = $pdo->prepare("
            SELECT id FROM bookings
            WHERE user_id      = :uid
              AND type         = 'surf'
              AND reference_id = :rid
              AND check_in     = :check_in
              AND status      != 'rejected'
        ");
        $chk->execute([
            ':uid'      => $user_id,
            ':rid'      => $reference_id,
            ':check_in' => $check_in,
        ]);
    }

    if ($chk->fetch()) {
        $errors[] = $type === 'hotel'
            ? "Vous avez déjà une réservation pour ces dates."
            : "Vous avez déjà réservé ce cours pour cette date.";
    }
}

// ─── Insert booking ───────────────────────────────────────
if (empty($errors)) {
    $pdo->prepare("
        INSERT INTO bookings
            (user_id, type, reference_id, check_in, check_out, guests, total_price, status, created_at)
        VALUES
            (:uid, :type, :rid, :check_in, :check_out, :guests, :price, 'pending', NOW())
    ")->execute([
        ':uid'       => $user_id,
        ':type'      => $type,
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

        $pdo->prepare("INSERT INTO loyalty_history (user_id, points, reason, created_at) VALUES (:uid, :pts, :reason, NOW())")
            ->execute([
                ':uid'    => $user_id,
                ':pts'    => $points,
                ':reason' => "Réservation #$booking_id",
            ]);
    }

    // ─── Notification ─────────────────────────────────────
    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (:uid, :title, :message, 0, NOW())
    ")->execute([
        ':uid'     => $user_id,
        ':title'   => $type === 'hotel' ? '🏨 Réservation hôtel reçue' : '🏄 Réservation surf reçue',
        ':message' => "Votre réservation #$booking_id est en attente de confirmation.",
    ]);

    // ─── Redirect ─────────────────────────────────────────
// ✅
header("Location: booking-details.php?id=$booking_id&success=1");
    exit;
}

// ─── Errors → redirect back ───────────────────────────────
// ✅ FIX 3 — redirect correct selon le type
$_SESSION['booking_errors'] = $errors;
$back = $type === 'hotel'
    ? "../hotel-details.php?id=$reference_id&booking_error=1"
    : "../surf-course-details.php?id=$reference_id&booking_error=1";
header("Location: $back");
exit;