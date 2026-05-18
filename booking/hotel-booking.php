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
    header('Location: ../hotels.php');
    exit;
}

// ─── Get data ─────────────────────────────────────────────
$user_id      = $_SESSION['user_id'];
$type         = isset($_POST['type']) ? trim($_POST['type']) : '';
$reference_id = isset($_POST['reference_id']) ? (int)$_POST['reference_id'] : 0;
$check_in     = isset($_POST['check_in'])  ? trim($_POST['check_in'])  : '';
$check_out    = isset($_POST['check_out']) ? trim($_POST['check_out']) : '';
$guests       = isset($_POST['guests'])    ? (int)$_POST['guests']     : 1;

// ─── Validation ───────────────────────────────────────────
$errors = [];

if (!in_array($type, ['hotel', 'surf'])) {
    $errors[] = "Type de réservation invalide.";
}
if (!$reference_id) {
    $errors[] = "Référence invalide.";
}
if (empty($check_in) || empty($check_out)) {
    $errors[] = "Les dates sont requises.";
}
if (!empty($check_in) && !empty($check_out)) {
    $d1 = new DateTime($check_in);
    $d2 = new DateTime($check_out);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($d1 < $today) {
        $errors[] = "La date d'arrivée ne peut pas être dans le passé.";
    }
    if ($d2 <= $d1) {
        $errors[] = "La date de départ doit être après la date d'arrivée.";
    }
}

// ─── Get price ────────────────────────────────────────────
$price = 0;
if (empty($errors)) {
    if ($type === 'hotel') {
        $stmt = $pdo->prepare("SELECT price FROM hotels WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT price FROM surf_courses WHERE id = :id");
    }
    $stmt->execute([':id' => $reference_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $errors[] = "Élément introuvable.";
    } else {
        $d1    = new DateTime($check_in);
        $d2    = new DateTime($check_out);
        $nights = $d2->diff($d1)->days;
        $price  = $item['price'] * $nights;
    }
}

// ─── Check double booking ─────────────────────────────────
if (empty($errors)) {
    $chk = $pdo->prepare("
        SELECT id FROM bookings
        WHERE user_id = :uid
        AND type = :type
        AND reference_id = :rid
        AND status != 'rejected'
        AND (
            (check_in <= :check_out AND check_out >= :check_in)
        )
    ");
    $chk->execute([
        ':uid'       => $user_id,
        ':type'      => $type,
        ':rid'       => $reference_id,
        ':check_in'  => $check_in,
        ':check_out' => $check_out,
    ]);
    if ($chk->fetch()) {
        $errors[] = "Vous avez déjà une réservation pour ces dates.";
    }
}

// ─── Insert booking ───────────────────────────────────────
if (empty($errors)) {
    $stmt = $pdo->prepare("
        INSERT INTO bookings (user_id, type, reference_id, check_in, check_out, guests, total_price, status)
        VALUES (:uid, :type, :rid, :check_in, :check_out, :guests, :price, 'pending')
    ");
    $stmt->execute([
        ':uid'       => $user_id,
        ':type'      => $type,
        ':rid'       => $reference_id,
        ':check_in'  => $check_in,
        ':check_out' => $check_out,
        ':guests'    => $guests,
        ':price'     => $price,
    ]);

    $booking_id = $pdo->lastInsertId();

    // ─── Add loyalty points ───────────────────────────────
    $points = floor($price / 100); // 1 point per 100 MAD
    if ($points > 0) {
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + :pts WHERE id = :uid")
            ->execute([':pts' => $points, ':uid' => $user_id]);

        $pdo->prepare("INSERT INTO loyalty_history (user_id, points, reason) VALUES (:uid, :pts, :reason)")
            ->execute([
                ':uid'    => $user_id,
                ':pts'    => $points,
                ':reason' => "Réservation #$booking_id",
            ]);
    }

    // ─── Notification ─────────────────────────────────────
    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message)
        VALUES (:uid, :title, :message)
    ")->execute([
        ':uid'     => $user_id,
        ':title'   => '✅ Réservation confirmée',
        ':message' => "Votre réservation #$booking_id est en attente de confirmation.",
    ]);

    // ─── Redirect to success ──────────────────────────────
    header("Location: booking-details.php?id=$booking_id&success=1");
    exit;
}

// ─── Errors → redirect back ───────────────────────────────
$_SESSION['booking_errors'] = $errors;
$back = $type === 'hotel'
    ? "../hotel-details.php?id=$reference_id"
    : "../course-details.php?id=$reference_id";
header("Location: $back");
exit;