<?php
// ============================================================
// chat/fetch-messages.php
// كيجيب messages في les deux sens (user↔admin)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$me      = (int)$_SESSION['user_id'];
$with    = (int)($_GET['with']    ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if (!$with) {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant']);
    exit;
}

// ── Mark incoming كـ read ──────────────────────────────────
$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
")->execute([$with, $me]);

// ── Fetch messages ─────────────────────────────────────────
if ($last_id > 0) {
    // فقط messages جديدة بعد last_id
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, subject, is_read, created_at
        FROM messages
        WHERE (
            (sender_id = ? AND receiver_id = ?)
            OR
            (sender_id = ? AND receiver_id = ?)
        )
        AND id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$me, $with, $with, $me, $last_id]);
} else {
    // Premier chargement — آخر 60 message
    $stmt = $pdo->prepare("
        SELECT * FROM (
            SELECT id, sender_id, receiver_id, message, subject, is_read, created_at
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC
            LIMIT 60
        ) sub
        ORDER BY created_at ASC
    ");
    $stmt->execute([$me, $with, $with, $me]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Build messages array ───────────────────────────────────
$messages = array_map(function($r) {
    return [
        'id'         => (int)$r['id'],
        'sender_id'  => (int)$r['sender_id'],
        'receiver_id'=> (int)$r['receiver_id'],
        'message'    => $r['message'],
        'subject'    => $r['subject'] ?? null,
        'is_read'    => (int)$r['is_read'],
        'created_at' => $r['created_at'],
    ];
}, $rows);

// ── Online status ──────────────────────────────────────────
$lastActivity = $pdo->prepare("
    SELECT MAX(created_at) FROM messages WHERE sender_id = ?
");
$lastActivity->execute([$with]);
$lastMsg  = $lastActivity->fetchColumn();
$isOnline = $lastMsg && (time() - strtotime($lastMsg)) < 300;

// ── Unread count ───────────────────────────────────────────
$unreadStmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
");
$unreadStmt->execute([$with, $me]);
$unreadCount = (int)$unreadStmt->fetchColumn();

echo json_encode([
    'success'      => true,
    'messages'     => $messages,
    'online'       => $isOnline,
    'unread_count' => $unreadCount,
    'server_time'  => date('Y-m-d H:i:s'),
]);