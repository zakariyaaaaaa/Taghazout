<?php
/**
 * map-filters.php
 * API endpoint — returns available filter categories with place counts
 * Response example:
 * [
 *   { "category": "hotel",      "count": 12 },
 *   { "category": "surf",       "count": 8  },
 *   { "category": "restaurant", "count": 5  },
 *   { "category": "activity",   "count": 7  }
 * ]
 */

require_once 'includes/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("
        SELECT category, COUNT(*) AS count
        FROM map_places
        WHERE latitude IS NOT NULL
          AND longitude IS NOT NULL
        GROUP BY category
        ORDER BY count DESC
    ");

    $filters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast count to int
    foreach ($filters as &$f) {
        $f['count'] = (int)$f['count'];
    }
    unset($f);

    echo json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}