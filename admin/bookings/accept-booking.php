<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $pdo->prepare("UPDATE bookings SET status = 'accepted' WHERE id = :id")
        ->execute([':id' => $id]);

    // Fetch booking user
    $booking = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $booking->execute([':id' => $id]);
    $booking = $booking->fetch();

    if ($booking) {
        // Notification to user
        $pdo->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (:uid, :title, :msg)
        ")->execute([
            ':uid'   => $booking['user_id'],
            ':title' => '✅ Réservation confirmée',
            ':msg'   => "Votre réservation #$id a été confirmée par l'administrateur.",
        ]);

        // Add loyalty points
        $points = floor($booking['total_price'] / 100);
        if ($points > 0) {
            $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + :pts WHERE id = :uid")
                ->execute([':pts' => $points, ':uid' => $booking['user_id']]);

            $pdo->prepare("INSERT INTO loyalty_history (user_id, points, reason) VALUES (:uid, :pts, :reason)")
                ->execute([
                    ':uid'    => $booking['user_id'],
                    ':pts'    => $points,
                    ':reason' => "Réservation #$id confirmée",
                ]);
        }
    }
}

header('Location: bookings.php?success=accepted');
exit;