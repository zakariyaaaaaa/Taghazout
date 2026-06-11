<?php
// ============================================================
// includes/mark-read.php
// ============================================================
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$me   = (int)$_SESSION['user_id'];
$from = (int)($_GET['from'] ?? 0);

if (!$from) {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant']);
    exit;
}

$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
")->execute([$from, $me]);

echo json_encode(['success' => true]);