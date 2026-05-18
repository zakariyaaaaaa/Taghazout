<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$body    = json_decode(file_get_contents('php://input'), true);
$action  = $body['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID invalide']);
        exit;
    }
    $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")
        ->execute([$id, $user_id]);
    echo json_encode(['success' => true]);

} elseif ($action === 'delete_all') {
    $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")
        ->execute([$user_id]);
    echo json_encode(['success' => true]);

} elseif ($action === 'mark_read') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
        ->execute([$user_id]);
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Action invalide']);
}