<?php
session_start();
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// ─── Get booking ──────────────────────────────────────────
$booking_id = (int)($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    header('Location: ../booking/my-bookings.php');
    exit;
}

// ─── Fetch booking ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $booking_id, ':uid' => $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Réservation introuvable.");
}

if ($booking['status'] === 'accepted') {
    die("Cette réservation est déjà payée.");
}

// ─── Get item name ────────────────────────────────────────
if ($booking['type'] === 'hotel') {
    $stmt = $pdo->prepare("SELECT name FROM hotels WHERE id = :id");
} else {
    $stmt = $pdo->prepare("SELECT title AS name FROM surf_courses WHERE id = :id");
}
$stmt->execute([':id' => $booking['reference_id']]);
$item = $stmt->fetch();
$item_name = $item['name'] ?? 'Réservation Taghazout';

// ─── Stripe ───────────────────────────────────────────────
\Stripe\Stripe::setApiKey('sk_test_51TOagtBLRMTX4QBlssAcnT5x5neZvW4G2pjZ8IJ6ncOXdWRhXaOhUM5VV316jXPCldLU8xOlV2c78xoPcrzDfTri00T9nZSBNE');

$checkout = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency'     => 'mad',
            'unit_amount'  => (int)($booking['total_price'] * 100),
            'product_data' => [
                'name'        => $item_name,
                'description' => 'Réservation du ' . date('d/m/Y', strtotime($booking['check_in'])) . ' au ' . date('d/m/Y', strtotime($booking['check_out'])),
            ],
        ],
        'quantity' => 1,
    ]],
    'mode'        => 'payment',
    'success_url' => 'http://localhost:3000/payment/payment-success.php?booking_id=' 
    . $booking_id . 
    '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => 'http://localhost:3000/payment/payment-failed.php?booking_id=' . $booking_id,
    'metadata'    => [
        'booking_id' => $booking_id,
        'user_id'    => $_SESSION['user_id'],
    ],
]);

// ─── Save session ID ──────────────────────────────────────
$pdo->prepare("UPDATE payments SET transaction_id = :sid WHERE booking_id = :bid")
    ->execute([':sid' => $checkout->id, ':bid' => $booking_id]);

header('Location: ' . $checkout->url);
exit;