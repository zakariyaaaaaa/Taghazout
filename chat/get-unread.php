<?php
// ============================================================
// includes/get-unread.php
// كيرجع badge count — poll كل 5s
// ============================================================
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$me = (int)$_SESSION['user_id'];

// Total unread لـ me
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages
    WHERE receiver_id = ? AND is_read = 0
");
$stmt->execute([$me]);
$count = (int)$stmt->fetchColumn();

// Unread per conversation (للـ sidebar badges)
$convStmt = $pdo->prepare("
    SELECT sender_id, COUNT(*) AS cnt
    FROM messages
    WHERE receiver_id = ? AND is_read = 0
    GROUP BY sender_id
");
$convStmt->execute([$me]);

$convUnread = [];
while ($row = $convStmt->fetch(PDO::FETCH_ASSOC)) {
    $convUnread[(int)$row['sender_id']] = (int)$row['cnt'];
}

echo json_encode([
    'success'     => true,
    'count'       => $count,
    'conv_unread' => $convUnread,
]);