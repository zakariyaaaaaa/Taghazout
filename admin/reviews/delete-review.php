<?php
session_start();
require_once '../../includes/config.php';

// ─── Admin check ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// ─── Only POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reviews.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: reviews.php?error=invalid_id");
    exit;
}

// ─── Check review exists ──────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();

if (!$review) {
    header("Location: reviews.php?error=not_found");
    exit;
}

// ─── Delete ───────────────────────────────────────────────
$del = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
$del->execute([$id]);

header("Location: reviews.php?success=deleted");
exit;