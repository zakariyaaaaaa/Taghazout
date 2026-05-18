<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: restaurants.php"); exit; }

// ── Fetch restaurant ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = :id");
$stmt->execute([':id' => $id]);
$restaurant = $stmt->fetch();
if (!$restaurant) { header("Location: restaurants.php"); exit; }

// ── Delete image file ─────────────────────────────────────
if (!empty($restaurant['image'])) {
    $img_path = '../../uploads/restaurants/' . $restaurant['image'];
    if (file_exists($img_path)) unlink($img_path);
}

// ── Delete from DB ────────────────────────────────────────
$pdo->prepare("DELETE FROM restaurants WHERE id = :id")->execute([':id' => $id]);

header("Location: restaurants.php?deleted=1");
exit;