<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$me = (int)$_SESSION['user_id'];

// ─── Update my last_seen (add column if needed) ───────────
// We use the messages table — last message sent = activity indicator
// Optionally: add last_seen column to users table

// ─── Get online users (sent message in last 5 min) ────────
// "Online" = last message sent < 5 minutes ago
$stmt = $pdo->prepare("
    SELECT DISTINCT
        u.id,
        u.name,
        u.avatar,
        u.role,
        MAX(m.created_at) AS last_active
    FROM users u
    JOIN messages m ON m.sender_id = u.id
    WHERE u.id != ?
    AND m.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    GROUP BY u.id
    ORDER BY last_active DESC
");
$stmt->execute([$me]);
$online = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Also return all users I have conversations with ──────
$allConv = $pdo->prepare("
    SELECT DISTINCT
        u.id, u.name, u.avatar, u.role
    FROM users u
    WHERE u.id != ?
    AND (
        EXISTS (SELECT 1 FROM messages WHERE sender_id = ? AND receiver_id = u.id)
        OR EXISTS (SELECT 1 FROM messages WHERE sender_id = u.id AND receiver_id = ?)
    )
");
$allConv->execute([$me, $me, $me]);
$allConv = $allConv->fetchAll(PDO::FETCH_ASSOC);

// ─── Build online IDs set ─────────────────────────────────
$onlineIds = array_column($online, 'id');

// ─── Merge: mark online status ────────────────────────────
$result = array_map(function($u) use ($onlineIds) {
    $u['is_online'] = in_array($u['id'], $onlineIds);
    return $u;
}, $allConv);

echo json_encode([
    'success'     => true,
    'online_ids'  => $onlineIds,
    'users'       => $result,
    'online_count' => count($onlineIds),
]);