<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

// ── Parse body ────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

$reference_id = isset($body['reference_id']) ? (int)$body['reference_id'] : 0;
$type         = isset($body['type'])         ? trim($body['type'])         : '';
$user_id      = (int)$_SESSION['user_id'];

$allowed_types = ['hotel', 'activity', 'surf', 'restaurant'];

if (!$reference_id || !in_array($type, $allowed_types, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres invalides']);
    exit;
}

// ── Check si déjà en favori ───────────────────────────────────
$check = $pdo->prepare("
    SELECT id FROM favorites
    WHERE user_id = ? AND reference_id = ? AND type = ?
");
$check->execute([$user_id, $reference_id, $type]);
$existing = $check->fetch();

if ($existing) {
    // Supprimer
    $pdo->prepare("DELETE FROM favorites WHERE id = ?")
        ->execute([$existing['id']]);

    echo json_encode(['action' => 'removed', 'added' => false]);
} else {
    // Ajouter
    $pdo->prepare("
        INSERT INTO favorites (user_id, reference_id, type, created_at)
        VALUES (?, ?, ?, NOW())
    ")->execute([$user_id, $reference_id, $type]);

    echo json_encode(['action' => 'added', 'added' => true]);
}