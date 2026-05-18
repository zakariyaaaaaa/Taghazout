<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = :id")
        ->execute([':id' => $id]);

    $booking = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $booking->execute([':id' => $id]);
    $booking = $booking->fetch();

    if ($booking) {
        $pdo->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (:uid, :title, :msg)
        ")->execute([
            ':uid'   => $booking['user_id'],
            ':title' => '❌ Réservation refusée',
            ':msg'   => "Votre réservation #$id a été refusée. Contactez-nous pour plus d'informations.",
        ]);
    }
}

header('Location: bookings.php?success=rejected');
exit;