<?php
/**
 * map-data.php
 * API endpoint — returns map places as JSON
 * GET params:
 *   category  (optional) hotel | activity | surf | restaurant | general
 *   q         (optional) full-text search on title / description
 *   limit     (optional, default 100)
 */

require_once 'includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// ── Input sanitization ────────────────────────────────────────
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$q        = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$limit    = isset($_GET['limit'])    ? (int)$_GET['limit']     : 100;
$limit    = max(1, min($limit, 500)); // clamp 1-500

// ── Allowed categories (whitelist) ────────────────────────────
$allowed_cats = ['hotel', 'activity', 'surf', 'restaurant', 'general'];

// ── Build query ───────────────────────────────────────────────
$conditions = ["latitude IS NOT NULL", "longitude IS NOT NULL"];
$params     = [];

if ($category !== '' && in_array($category, $allowed_cats, true)) {
    $conditions[] = "category = :category";
    $params[':category'] = $category;
}

if ($q !== '') {
    $conditions[] = "(title LIKE :q OR description LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

$where = 'WHERE ' . implode(' AND ', $conditions);
$sql   = "SELECT id, title, description, category, image, latitude, longitude
          FROM map_places
          $where
          ORDER BY created_at DESC
          LIMIT :limit";

// ── Execute ───────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast floats
    foreach ($places as &$p) {
        $p['latitude']  = (float)$p['latitude'];
        $p['longitude'] = (float)$p['longitude'];
    }
    unset($p);

    echo json_encode($places, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}