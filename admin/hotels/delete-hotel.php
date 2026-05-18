<?php
session_start();
require_once '../../includes/config.php';

// ─── Admin check ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// ─── Validate ID ──────────────────────────────────────────
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: hotels.php");
    exit;
}

// ─── Fetch hotel ──────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
$stmt->execute([':id' => $id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header("Location: hotels.php?error=not_found");
    exit;
}

// ─── Handle confirmed deletion ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    try {
        $pdo->beginTransaction();

        // 1. Delete related favorites
        $pdo->prepare("DELETE FROM favorites WHERE type = 'hotel' AND reference_id = :id")
            ->execute([':id' => $id]);

        // 2. Delete related reviews
        $pdo->prepare("DELETE FROM reviews WHERE hotel_id = :id")
            ->execute([':id' => $id]);

        // 3. Delete related bookings
        $pdo->prepare("DELETE FROM bookings WHERE type = 'hotel' AND reference_id = :id")
            ->execute([':id' => $id]);

        // 4. Delete hotel record
        $pdo->prepare("DELETE FROM hotels WHERE id = :id")
            ->execute([':id' => $id]);

        $pdo->commit();

        // 5. Delete image file
        if (!empty($hotel['image'])) {
            $img_path = '../../uploads/hotels/' . $hotel['image'];
            if (file_exists($img_path)) unlink($img_path);
        }

        header("Location: hotels.php?deleted=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $delete_error = 'Une erreur est survenue lors de la suppression.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer l'hôtel — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>

<div class="admin-shell">

<!-- ══════════════════════════════
     SIDEBAR
══════════════════════════════ -->
<aside class="admin-sidebar">
    <div class="sb-logo">
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text">
            <strong>Taghazout</strong>
            <span>Admin Panel</span>
        </div>
    </div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <a href="../analytics.php"><span class="nav-icon">📈</span><span>Analytics</span></a>
        <div class="sb-label">Contenu</div>
        <a href="hotels.php" class="active"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="../payments/payments.php"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
        <a href="../messages/messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
    </nav>
    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>

<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="admin-main">

<div class="admin-topbar">
    <div class="topbar-left">
        <div>
            <div class="topbar-title">Supprimer l'hôtel</div>
            <div class="topbar-breadcrumb">
                Home <span>/</span>
                <a href="hotels.php" style="color:inherit;text-decoration:none;">Hôtels</a>
                <span>/</span> Supprimer
            </div>
        </div>
    </div>
</div>

<div class="admin-body">

    <div class="page-header">
        <div>
            <h1 style="color:var(--color-danger);">🗑️ Supprimer l'hôtel</h1>
            <p>Cette action est irréversible</p>
        </div>
        <a href="hotels.php" class="btn btn-secondary">← Retour à la liste</a>
    </div>

    <?php if (!empty($delete_error)): ?>
    <div class="alert alert-danger" style="margin-bottom:1.5rem;">
        <span class="alert-icon">❌</span> <?= htmlspecialchars($delete_error) ?>
    </div>
    <?php endif; ?>

    <div style="max-width:640px;margin:0 auto;">

        <!-- Hotel preview card -->
        <div class="card" style="margin-bottom:1.5rem;border-color:rgba(214,69,69,0.2);">
            <div class="card-body">
                <div style="display:flex;gap:1.25rem;align-items:center;">
                    <img
                        src="../../uploads/hotels/<?= htmlspecialchars($hotel['image'] ?? '') ?>"
                        onerror="this.src='../../assets/images/default.jpg'"
                        alt="<?= htmlspecialchars($hotel['name']) ?>"
                        style="width:100px;height:80px;object-fit:cover;border-radius:var(--radius-md);flex-shrink:0;border:1px solid var(--border-subtle);"
                    >
                    <div>
                        <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:var(--ocean-deep);margin-bottom:.3rem;">
                            <?= htmlspecialchars($hotel['name']) ?>
                        </div>
                        <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem;">
                            📍 <?= htmlspecialchars($hotel['location']) ?>
                        </div>
                        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
                            <span style="font-weight:700;color:var(--ocean-teal);font-size:.9rem;">
                                <?= number_format($hotel['price'], 0, ',', ' ') ?> MAD / nuit
                            </span>
                            <span style="color:#f59e0b;font-size:.85rem;">
                                ⭐ <?= number_format($hotel['rating'], 1) ?>
                            </span>
                            <?php if (!empty($hotel['type'])): ?>
                            <span class="badge badge-info"><?= htmlspecialchars(ucfirst($hotel['type'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning card -->
        <div class="card" style="margin-bottom:1.5rem;border-color:rgba(214,69,69,0.25);background:rgba(214,69,69,0.02);">
            <div class="card-body">
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <div style="font-size:2rem;flex-shrink:0;">⚠️</div>
                    <div>
                        <div style="font-family:'Syne',sans-serif;font-weight:700;color:var(--color-danger);margin-bottom:.6rem;font-size:1rem;">
                            Attention — Action irréversible
                        </div>
                        <p style="font-size:.875rem;color:var(--text-secondary);line-height:1.7;margin-bottom:.75rem;">
                            La suppression de cet hôtel entraînera la suppression définitive de :
                        </p>
                        <ul style="font-size:.875rem;color:var(--text-secondary);line-height:2;padding-left:1.25rem;">
                            <li>Toutes les <strong>réservations</strong> liées à cet hôtel</li>
                            <li>Tous les <strong>avis</strong> et notes des clients</li>
                            <li>Tous les <strong>favoris</strong> des utilisateurs</li>
                            <li>La <strong>photo</strong> de l'hôtel</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm form -->
        <div class="card" style="border-color:rgba(214,69,69,0.2);">
            <div class="card-header" style="border-color:rgba(214,69,69,0.12);">
                <h3>Confirmer la suppression</h3>
            </div>
            <div class="card-body">
                <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1.25rem;">
                    Pour confirmer, tapez le nom de l'hôtel ci-dessous :
                    <strong style="color:var(--text-primary);"><?= htmlspecialchars($hotel['name']) ?></strong>
                </p>

                <form method="POST">
                    <div class="form-group" style="margin-bottom:1.25rem;">
                        <input
                            class="form-control"
                            type="text"
                            id="confirm-name"
                            placeholder="Tapez le nom de l'hôtel..."
                            autocomplete="off"
                            style="border-color:rgba(214,69,69,0.3);"
                        >
                    </div>

                    <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                        <a href="edit-hotel.php?id=<?= $id ?>" class="btn btn-secondary">
                            ← Annuler
                        </a>
                        <button
                            type="submit"
                            name="confirm_delete"
                            id="delete-btn"
                            class="btn btn-danger"
                            disabled
                            style="opacity:.5;cursor:not-allowed;"
                        >
                            🗑️ Supprimer définitivement
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

</div>
</main>
</div>

<script src="../../assets/js/main.js"></script>
<script>
// ── Enable delete button only when name matches ────────────
const expected = <?= json_encode($hotel['name']) ?>;
const input    = document.getElementById('confirm-name');
const btn      = document.getElementById('delete-btn');

input.addEventListener('input', () => {
    const match = input.value.trim() === expected.trim();
    btn.disabled       = !match;
    btn.style.opacity  = match ? '1'          : '.5';
    btn.style.cursor   = match ? 'pointer'    : 'not-allowed';
    input.style.borderColor = match
        ? 'var(--color-danger)'
        : 'rgba(214,69,69,0.3)';
});
</script>
</body>
</html>