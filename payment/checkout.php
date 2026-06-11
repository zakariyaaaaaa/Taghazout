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

// ─── Coupon ───────────────────────────────────────────────
$coupon_code = trim($_GET['coupon_code'] ?? '');
$final_price = (float)$booking['total_price'];
$coupon      = null;

if ($coupon_code) {
    $stmt = $pdo->prepare("
        SELECT lr.*, rw.value, rw.value_type, rw.title AS reward_title
        FROM loyalty_redemptions lr
        JOIN loyalty_rewards rw ON lr.reward_id = rw.id
        WHERE lr.coupon_code = ? AND lr.user_id  = ?
          AND lr.status      = 'active'
          AND (lr.expires_at IS NULL OR lr.expires_at > NOW())
    ");
    $stmt->execute([$coupon_code, $_SESSION['user_id']]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        $discount    = $coupon['value_type'] === 'percent'
            ? $final_price * ($coupon['value'] / 100)
            : min((float)$coupon['value'], $final_price);
        $final_price = max(0, $final_price - $discount);
    }
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
            'unit_amount'  => (int)($final_price * 100),
            'product_data' => [
                'name'        => $item_name . ($coupon ? ' (Coupon: ' . $coupon_code . ')' : ''),
                'description' => 'Réservation du ' . date('d/m/Y', strtotime($booking['check_in']))
                               . ' au ' . date('d/m/Y', strtotime($booking['check_out']))
                               . ($coupon ? ' — Réduction appliquée : ' . $coupon['reward_title'] : ''),
            ],
        ],
        'quantity' => 1,
    ]],
    'mode'        => 'payment',
    'success_url' => 'http://localhost:3000/payment/payment-success.php?booking_id='
        . $booking_id
        . '&coupon_code=' . urlencode($coupon_code)
        . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => 'http://localhost:3000/payment/payment-failed.php?booking_id=' . $booking_id,
    'metadata'    => [
        'booking_id'  => $booking_id,
        'user_id'     => $_SESSION['user_id'],
        'coupon_code' => $coupon_code,
    ],
]);

// ─── Save payment in DB ───────────────────────────────────
$check = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ?");
$check->execute([$booking_id]);

if ($check->fetch()) {
    $pdo->prepare("UPDATE payments SET transaction_id = ?, amount = ?, status = 'pending' WHERE booking_id = ?")
        ->execute([$checkout->id, $final_price, $booking_id]);
} else {
    $pdo->prepare("INSERT INTO payments (user_id, booking_id, amount, method, status, transaction_id) VALUES (?, ?, ?, 'stripe', 'pending', ?)")
        ->execute([$_SESSION['user_id'], $booking_id, $final_price, $checkout->id]);
}

header('Location: ' . $checkout->url);
exit;