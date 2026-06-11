<?php
// ============================================================
// admin/messages/send-message.php
// للـ admin فقط — INSERT message جديد
// ============================================================
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messages.php"); exit;
}

$adminId     = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message']      ?? '');
$subject     = trim($_POST['subject']      ?? '');

if (!$receiver_id || $message === '') {
    header("Location: messages.php?error=missing"); exit;
}

// تأكد من وجود المستلم
$check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role != 'admin'");
$check->execute([$receiver_id]);
if (!$check->fetch()) {
    header("Location: messages.php?error=invalid_user"); exit;
}

// INSERT message جديد — sender = admin
$stmt = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, subject, is_read, created_at)
    VALUES (?, ?, ?, ?, 0, NOW())
");
$stmt->execute([$adminId, $receiver_id, $message, $subject ?: null]);

header("Location: reply-message.php?sender_id={$receiver_id}&sent=1");
exit;