<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// ─── Fetch booking ────────────────────────────────────────
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: bookings.php'); exit; }

$stmt = $pdo->prepare("
    SELECT b.*,
        u.name  AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        u.avatar AS user_avatar,
        u.loyalty_points,
        COALESCE(h.name,  s.title)    AS item_name,
        COALESCE(h.image, s.image)    AS item_image,
        COALESCE(h.location, NULL)    AS item_location,
        COALESCE(h.stars,  NULL)      AS hotel_stars,
        h.rating                      AS item_rating,
        COALESCE(h.price,  s.price)   AS item_price,
        h.type  AS hotel_type,
        h.id    AS hotel_id,
        s.id    AS surf_id
    FROM bookings b
    LEFT JOIN users        u ON b.user_id      = u.id
    LEFT JOIN hotels       h ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf'  AND b.reference_id = s.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();
if (!$b) { header('Location: bookings.php'); exit; }

// ─── Handle status change ─────────────────────────────────
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $new_status = $_POST['change_status'];
    if (in_array($new_status, ['pending', 'accepted', 'rejected'])) {

        $pdo->prepare("UPDATE bookings SET status = :s WHERE id = :id")
            ->execute([':s' => $new_status, ':id' => $id]);

        // Add loyalty points when accepted
        if ($new_status === 'accepted' && $b['status'] !== 'accepted') {
            $points = max(1, (int)($b['total_price'] / 100));
            $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + :p WHERE id = :uid")
                ->execute([':p' => $points, ':uid' => $b['user_id']]);
            $pdo->prepare("
                INSERT INTO loyalty_history (user_id, points, reason, created_at)
                VALUES (:uid, :p, :r, NOW())
            ")->execute([
                ':uid' => $b['user_id'],
                ':p'   => $points,
                ':r'   => 'Réservation #' . $id . ' confirmée',
            ]);

            // Notification
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, is_read, created_at)
                VALUES (:uid, :t, :m, 0, NOW())
            ")->execute([
                ':uid' => $b['user_id'],
                ':t'   => '✅ Réservation confirmée',
                ':m'   => 'Votre réservation #' . $id . ' pour « ' . $b['item_name'] . ' » a été confirmée. Vous avez gagné ' . $points . ' points fidélité.',
            ]);
        }

        if ($new_status === 'rejected' && $b['status'] !== 'rejected') {
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, is_read, created_at)
                VALUES (:uid, :t, :m, 0, NOW())
            ")->execute([
                ':uid' => $b['user_id'],
                ':t'   => '❌ Réservation refusée',
                ':m'   => 'Votre réservation #' . $id . ' pour « ' . $b['item_name'] . ' » a été refusée. Contactez-nous pour plus d\'informations.',
            ]);
        }

        // Refresh
        $stmt->execute([':id' => $id]);
        $b = $stmt->fetch();
        $flash = 'Statut mis à jour avec succès.';
    }
}

// ─── Helpers ──────────────────────────────────────────────
$nights = 0;
if ($b['check_in'] && $b['check_out']) {
    $nights = max(0, (new DateTime($b['check_in']))->diff(new DateTime($b['check_out']))->days);
}

$status_cfg = [
    'pending'  => ['label' => 'En attente', 'badge' => 'badge-warning', 'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => '⏳'],
    'accepted' => ['label' => 'Confirmée',  'badge' => 'badge-success', 'color' => '#0e7c6b', 'bg' => '#edfaf5', 'icon' => '✅'],
    'rejected' => ['label' => 'Refusée',    'badge' => 'badge-danger',  'color' => '#d64545', 'bg' => '#fff2f2', 'icon' => '❌'],
];
$sc = $status_cfg[$b['status']] ?? $status_cfg['pending'];

function avatarSrc($avatar) {
    $path = '../../uploads/users/' . $avatar;
    return (!empty($avatar) && file_exists($path)) ? $path : '../../assets/images/default.jpg';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation #<?= $id ?> — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hôtels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activités</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="bookings.php" class="active"><span class="nav-icon">📅</span><span>Réservations</span></a>
        <a href="../payments/payments.php"><span class="nav-icon">💳</span><span>Paiements</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Utilisateurs</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Avis</span></a>
        <a href="../messages/messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
        <div class="sb-label">Paramètres</div>
        <a href="../../index.php"><span class="nav-icon">🌐</span><span>Voir le site</span></a>
        <a href="../../auth/logout.php"><span class="nav-icon">🚪</span><span>Déconnexion</span></a>
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
            <div class="topbar-title">Réservation #<?= $id ?></div>
            <div class="topbar-breadcrumb">
                <a href="../dashboard.php" style="color:inherit;text-decoration:none;">Home</a>
                <span>/</span>
                <a href="bookings.php" style="color:inherit;text-decoration:none;">Réservations</a>
                <span>/</span> #<?= $id ?>
            </div>
        </div>
    </div>
    <div class="topbar-right">
        <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
    </div>
</div>

<div class="admin-body">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1>📋 Réservation #<?= $id ?></h1>
            <p>Créée le <?= date('d/m/Y à H:i', strtotime($b['created_at'])) ?></p>
        </div>
        <div style="display:flex;gap:.75rem;align-items:center;">
            <span class="badge <?= $sc['badge'] ?>" style="font-size:.85rem;padding:.35rem .9rem;">
                <?= $sc['icon'] ?> <?= $sc['label'] ?>
            </span>
            <a href="bookings.php" class="btn btn-secondary">← Retour</a>
        </div>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="alert alert-success" style="margin-bottom:1.5rem;">
        <span class="alert-icon">✅</span> <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

        <!-- ══ LEFT COLUMN ══ -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <!-- Item card -->
            <div class="card">
                <div class="card-header">
                    <h3><?= $b['type'] === 'hotel' ? '🏨 Hôtel réservé' : '🏄 Cours de surf réservé' ?></h3>
                    <?php
                    $detail_url = $b['type'] === 'hotel'
                        ? '../../hotel-details.php?id=' . $b['hotel_id']
                        : '../../course-details.php?id=' . $b['surf_id'];
                    ?>
                    <a href="<?= $detail_url ?>" target="_blank" class="btn btn-secondary btn-sm">
                        👁️ Voir la page
                    </a>
                </div>
                <div class="card-body">
                    <div style="display:flex;gap:1.25rem;align-items:flex-start;">
                        <img
                            src="../../uploads/<?= $b['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($b['item_image'] ?? '') ?>"
                            onerror="this.src='../../assets/images/default.jpg'"
                            alt="<?= htmlspecialchars($b['item_name'] ?? '') ?>"
                            style="width:140px;height:110px;object-fit:cover;border-radius:var(--radius-md);flex-shrink:0;border:1px solid var(--border-subtle);"
                        >
                        <div style="flex:1;">
                            <div style="font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;color:var(--ocean-deep);margin-bottom:.4rem;">
                                <?= htmlspecialchars($b['item_name'] ?? 'N/A') ?>
                            </div>
                            <?php if ($b['item_location']): ?>
                            <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.5rem;">
                                📍 <?= htmlspecialchars($b['item_location']) ?>
                            </div>
                            <?php endif; ?>
                            <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
                                <?php if ($b['item_rating']): ?>
                                <span style="color:#f59e0b;font-weight:600;font-size:.9rem;">
                                    ⭐ <?= number_format($b['item_rating'], 1) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($b['hotel_stars']): ?>
                                <span style="color:var(--sand-warm);font-size:.88rem;">
                                    <?= str_repeat('★', $b['hotel_stars']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($b['hotel_type']): ?>
                                <span class="badge badge-info"><?= htmlspecialchars(ucfirst($b['hotel_type'])) ?></span>
                                <?php endif; ?>
                                <span style="font-weight:700;color:var(--ocean-teal);">
                                    <?= number_format($b['item_price'], 0, ',', ' ') ?> MAD / nuit
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dates & pricing -->
            <div class="card">
                <div class="card-header">
                    <h3>📆 Dates & Tarification</h3>
                </div>
                <div class="card-body">

                    <!-- Timeline -->
                    <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:1rem;align-items:center;margin-bottom:1.5rem;">
                        <div style="background:var(--color-info-bg);border-radius:var(--radius-md);padding:1rem 1.25rem;text-align:center;">
                            <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);font-weight:600;margin-bottom:.4rem;">Check-in</div>
                            <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--ocean-deep);">
                                <?= date('d', strtotime($b['check_in'])) ?>
                            </div>
                            <div style="font-size:.82rem;color:var(--text-muted);">
                                <?= date('M Y', strtotime($b['check_in'])) ?>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.2rem;">
                                <?= date('l', strtotime($b['check_in'])) ?>
                            </div>
                        </div>

                        <div style="text-align:center;">
                            <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--ocean-teal);"><?= $nights ?></div>
                            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">nuit<?= $nights > 1 ? 's' : '' ?></div>
                            <div style="color:var(--text-muted);font-size:1rem;margin-top:.3rem;">→</div>
                        </div>

                        <div style="background:var(--color-success-bg);border-radius:var(--radius-md);padding:1rem 1.25rem;text-align:center;">
                            <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);font-weight:600;margin-bottom:.4rem;">Check-out</div>
                            <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--ocean-deep);">
                                <?= date('d', strtotime($b['check_out'])) ?>
                            </div>
                            <div style="font-size:.82rem;color:var(--text-muted);">
                                <?= date('M Y', strtotime($b['check_out'])) ?>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.2rem;">
                                <?= date('l', strtotime($b['check_out'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Price breakdown -->
                    <div style="border:1px solid var(--border-subtle);border-radius:var(--radius-md);overflow:hidden;">
                        <div style="padding:.85rem 1.1rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-subtle);background:var(--bg-surface);">
                            <span style="font-size:.875rem;color:var(--text-secondary);">Prix / nuit</span>
                            <span style="font-weight:600;color:var(--text-primary);"><?= number_format($b['item_price'], 0, ',', ' ') ?> MAD</span>
                        </div>
                        <div style="padding:.85rem 1.1rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-subtle);">
                            <span style="font-size:.875rem;color:var(--text-secondary);">Nombre de nuits</span>
                            <span style="font-weight:600;color:var(--text-primary);"><?= $nights ?> nuit<?= $nights > 1 ? 's' : '' ?></span>
                        </div>
                        <?php if (!empty($b['guests'])): ?>
                        <div style="padding:.85rem 1.1rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-subtle);">
                            <span style="font-size:.875rem;color:var(--text-secondary);">Voyageurs</span>
                            <span style="font-weight:600;color:var(--text-primary);"><?= (int)$b['guests'] ?> personne<?= $b['guests'] > 1 ? 's' : '' ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="padding:1rem 1.1rem;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--ocean-deep),var(--ocean-teal));">
                            <span style="font-weight:700;color:rgba(255,255,255,.85);font-size:.9rem;">Total</span>
                            <span style="font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:800;color:#fff;">
                                <?= number_format($b['total_price'], 0, ',', ' ') ?> MAD
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($b['notes'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3>📝 Notes du client</h3>
                </div>
                <div class="card-body">
                    <p style="font-size:.9rem;color:var(--text-secondary);line-height:1.8;background:var(--bg-surface);padding:1rem;border-radius:var(--radius-sm);border-left:3px solid var(--ocean-teal);">
                        <?= nl2br(htmlspecialchars($b['notes'])) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ══ RIGHT COLUMN ══ -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <!-- Status management -->
            <div class="card">
                <div class="card-header">
                    <h3>⚙️ Gérer le statut</h3>
                </div>
                <div class="card-body">
                    <!-- Current status -->
                    <div style="text-align:center;padding:1.1rem;border-radius:var(--radius-md);background:<?= $sc['bg'] ?>;margin-bottom:1.25rem;">
                        <div style="font-size:1.6rem;margin-bottom:.35rem;"><?= $sc['icon'] ?></div>
                        <div style="font-family:'Syne',sans-serif;font-weight:700;color:<?= $sc['color'] ?>;font-size:1rem;">
                            <?= $sc['label'] ?>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div style="display:flex;flex-direction:column;gap:.6rem;">
                        <?php if ($b['status'] !== 'accepted'): ?>
                        <form method="POST">
                            <button
                                type="submit"
                                name="change_status"
                                value="accepted"
                                class="btn btn-success"
                                style="width:100%;justify-content:center;"
                                onclick="return confirm('Confirmer cette réservation ?')"
                            >
                                ✅ Confirmer la réservation
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($b['status'] !== 'rejected'): ?>
                        <form method="POST">
                            <button
                                type="submit"
                                name="change_status"
                                value="rejected"
                                class="btn btn-danger"
                                style="width:100%;justify-content:center;"
                                onclick="return confirm('Refuser cette réservation ?')"
                            >
                                ❌ Refuser la réservation
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($b['status'] !== 'pending'): ?>
                        <form method="POST">
                            <button
                                type="submit"
                                name="change_status"
                                value="pending"
                                class="btn btn-secondary"
                                style="width:100%;justify-content:center;"
                            >
                                ⏳ Remettre en attente
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Client info -->
            <div class="card">
                <div class="card-header">
                    <h3>👤 Client</h3>
                    <a href="../users/users.php?search=<?= urlencode($b['user_email']) ?>" class="btn btn-secondary btn-sm">
                        Voir profil
                    </a>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:1.1rem;padding-bottom:1.1rem;border-bottom:1px solid var(--border-subtle);">
                        <img
                            src="<?= avatarSrc($b['user_avatar']) ?>"
                            alt="<?= htmlspecialchars($b['user_name']) ?>"
                            style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--ocean-foam);flex-shrink:0;"
                        >
                        <div>
                            <div style="font-family:'Syne',sans-serif;font-weight:700;color:var(--ocean-deep);font-size:.95rem;">
                                <?= htmlspecialchars($b['user_name'] ?? 'N/A') ?>
                            </div>
                            <div style="font-size:.78rem;color:var(--text-muted);">
                                <?= htmlspecialchars($b['user_email'] ?? '') ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.65rem;">
                        <?php if ($b['user_phone']): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.8rem;color:var(--text-muted);">📞 Téléphone</span>
                            <span style="font-size:.85rem;font-weight:500;color:var(--text-primary);">
                                <?= htmlspecialchars($b['user_phone']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.8rem;color:var(--text-muted);">🏅 Points fidélité</span>
                            <span style="font-size:.85rem;font-weight:700;color:var(--sand-warm);">
                                <?= number_format($b['loyalty_points'] ?? 0) ?> pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking meta -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 Détails</h3>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:.7rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:.7rem;border-bottom:1px solid var(--border-subtle);">
                        <span style="font-size:.8rem;color:var(--text-muted);">ID Réservation</span>
                        <span style="font-weight:700;color:var(--ocean-teal);">#<?= $b['id'] ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:.7rem;border-bottom:1px solid var(--border-subtle);">
                        <span style="font-size:.8rem;color:var(--text-muted);">Type</span>
                        <span class="badge badge-info">
                            <?= $b['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:.7rem;border-bottom:1px solid var(--border-subtle);">
                        <span style="font-size:.8rem;color:var(--text-muted);">Nuits</span>
                        <span style="font-weight:600;color:var(--text-primary);"><?= $nights ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:.7rem;border-bottom:1px solid var(--border-subtle);">
                        <span style="font-size:.8rem;color:var(--text-muted);">Statut</span>
                        <span class="badge <?= $sc['badge'] ?>"><?= $sc['icon'] ?> <?= $sc['label'] ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:.7rem;border-bottom:1px solid var(--border-subtle);">
                        <span style="font-size:.8rem;color:var(--text-muted);">Créée le</span>
                        <span style="font-size:.82rem;color:var(--text-secondary);">
                            <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:.8rem;color:var(--text-muted);">Total</span>
                        <span style="font-family:'Syne',sans-serif;font-weight:800;color:var(--ocean-teal);font-size:1rem;">
                            <?= number_format($b['total_price'], 0, ',', ' ') ?> MAD
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick nav -->
            <div style="display:flex;gap:.6rem;">
                <?php if ($id > 1): ?>
                <a href="booking-details.php?id=<?= $id - 1 ?>" class="btn btn-secondary" style="flex:1;justify-content:center;">
                    ← Préc.
                </a>
                <?php endif; ?>
                <a href="booking-details.php?id=<?= $id + 1 ?>" class="btn btn-secondary" style="flex:1;justify-content:center;">
                    Suiv. →
                </a>
            </div>

        </div>
    </div>

</div>
</main>
</div>
</body>
</html>