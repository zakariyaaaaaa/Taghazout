<?php
// ============================================================
// includes/send-message.php
// كيخدم ل user و admin — INSERT دايما row جديد
// ============================================================
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
    exit;
}

$me          = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');
$subject     = trim($_POST['subject'] ?? '');

if (!$receiver_id || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

// تأكد من وجود المستلم
$check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$check->execute([$receiver_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Destinataire introuvable']);
    exit;
}

// INSERT message جديد دايما
$stmt = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, subject, is_read, created_at)
    VALUES (?, ?, ?, ?, 0, NOW())
");
$stmt->execute([$me, $receiver_id, $message, $subject ?: null]);
$newId = (int)$pdo->lastInsertId();

// ── إلا جات من admin panel، redirect ──────────────────────
$isAdmin  = ($_SESSION['role'] ?? '') === 'admin';
$isAjax   = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if (!$isAjax) {
    // Form HTML عادي من admin panel
    if ($isAdmin) {
        header("Location: reply-message.php?sender_id={$receiver_id}&sent=1");
    } else {
        header("Location: chat.php?with={$receiver_id}");
    }
    exit;
}

// ── AJAX response ──────────────────────────────────────────
echo json_encode([
    'success' => true,
    'id'      => $newId,
    'message' => [
        'id'         => $newId,
        'sender_id'  => $me,
        'receiver_id'=> $receiver_id,
        'message'    => $message,
        'subject'    => $subject ?: null,
        'is_read'    => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]
]);