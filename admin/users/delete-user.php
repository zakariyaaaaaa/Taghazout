<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: users.php'); exit; }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: users.php'); exit; }


// Empêcher suppression de son propre compte
$self_delete = ($id === (int)($_SESSION['user_id'] ?? 0));

// Traitement de la suppression confirmée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && !$self_delete) {
    // 
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    // Supprimer aussi bookings, reviews, etc. selon besoin
    header('Location: users.php?success=supprime'); exit;
}
            ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supprimer utilisateur – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.delete-wrap { max-width: 560px; margin: 0 auto; }

.danger-header {
    background: rgba(239,68,68,.06);
    border: 1px solid rgba(239,68,68,.2);
    border-radius: var(--radius) var(--radius) 0 0;
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    border-bottom: none;
}
.danger-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(239,68,68,.1); color: #ef4444;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 1rem;
    border: 2px solid rgba(239,68,68,.2);
}
.danger-header h2 { font-size: 1.3rem; font-weight: 700; color: #ef4444; }
.danger-header p  { font-size: .875rem; color: var(--text-light); margin-top: .5rem; }

.user-preview {
    background: var(--white);
    border: 1px solid rgba(239,68,68,.15);
    border-top: none; border-bottom: none;
    padding: 1.4rem 2rem;
}
.user-av-lg {
    width: 52px; height: 52px; border-radius: 50%;
    background: var(--color-info-bg); color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; font-weight: 700; flex-shrink: 0;
}
.consequence-list { list-style: none; padding: 0; }
.consequence-list li {
    display: flex; align-items: flex-start; gap: .6rem;
    padding: .6rem 0; border-bottom: 1px solid var(--border);
    font-size: .845rem; color: var(--text-light);
}
.consequence-list li:last-child { border-bottom: none; }
.consequence-list li span.icon { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }

.confirm-actions {
    background: var(--white);
    border: 1px solid rgba(239,68,68,.2);
    border-top: 1px solid var(--border);
    border-radius: 0 0 var(--radius) var(--radius);
    padding: 1.4rem 2rem;
    display: flex; gap: .8rem; justify-content: flex-end; align-items: center;
}

/* Self-delete warning */
.self-warn {
    background: rgba(245,158,11,.07); border: 1px solid rgba(245,158,11,.25);
    border-radius: 9px; padding: 1rem 1.2rem; color: #f59e0b;
    font-size: .875rem; display: flex; gap: .7rem; align-items: flex-start;
    margin-bottom: 1.4rem;
}

@media(max-width:900px){ .admin-sidebar{display:none;} .admin-main{margin-left:0;} }
</style>
</head>
<body>
<div class="admin-shell">

<aside class="admin-sidebar">
    <div class="sb-logo">
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div>
    </div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <a href="../analytics.php"><span class="nav-icon">📈</span><span>Analytics</span></a>
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="../payments/payments.php"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="users.php" class="active"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
        <a href="../messages/messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
    </nav>
    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Supprimer utilisateur</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="users.php" style="color:var(--text-light);text-decoration:none;">Users</a>
                    <span>/</span> Supprimer #<?= $id ?>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">
        <div class="page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Supprimer l'utilisateur</h1>
                <p>Cette action est irréversible</p>
            </div>
        </div>

        <div class="delete-wrap">

            <?php if ($self_delete): ?>
            <div class="self-warn">
                ⚠️
                <div><strong>Impossible de supprimer votre propre compte.</strong><br>Demandez à un autre administrateur d'effectuer cette action.</div>
            </div>
            <?php endif; ?>

            <!-- Danger header -->
            <div class="danger-header">
                <div class="danger-icon">🗑️</div>
                <h2>Confirmer la suppression</h2>
                <p>Vous êtes sur le point de supprimer définitivement ce compte utilisateur.</p>
            </div>

            <!-- User preview -->
            <div class="user-preview">
                <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:var(--bg);border-radius:10px;margin-bottom:1.4rem;">
                   <div class="user-av-lg"><?= strtoupper(substr($user['name'],0,2)) ?></div>
<div>
    <div style="font-weight:700;font-size:.95rem;"><?= htmlspecialchars($user['name']) ?></div>
    <div style="font-size:.8rem;color:var(--text-light);font-family:'DM Mono',monospace;"><?= htmlspecialchars($user['email']) ?></div>
    <div style="font-size:.75rem;color:var(--text-light);margin-top:4px;">
        <?php
        $rl = ['admin'=>'👑 Admin', 'user'=>'🌊 User'];
        echo $rl[$user['role']] ?? ucfirst($user['role']);
        ?> · Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?>
    </div>
</div>

                </div>

                <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-light);margin-bottom:.8rem;">
                    ⚠️ Ce qui sera supprimé
                </div>
                <ul class="consequence-list">
                    <li><span class="icon">👤</span> Profil et informations personnelles</li>
                    <li><span class="icon">📅</span> Toutes les réservations associées</li>
                    <li><span class="icon">⭐</span> Tous les avis et commentaires</li>
                    <li><span class="icon">💬</span> Tous les messages envoyés</li>
                    <li><span class="icon">💳</span> Historique des paiements (les transactions resteront dans le système)</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="confirm-actions">
                <a href="users.php"
                   style="padding:.65rem 1.2rem;border-radius:8px;border:1px solid var(--border);color:var(--text);text-decoration:none;font-size:.875rem;font-weight:600;">
                    ← Annuler
                </a>
                <?php if (!$self_delete): ?>
                <form method="POST" action="delete-user.php?id=<?= $id ?>" style="margin:0;">
                    <button type="submit" name="confirm_delete" value="1"
                            style="padding:.65rem 1.4rem;border-radius:8px;background:#ef4444;color:#fff;border:none;font-size:.875rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;font-family:'DM Sans',sans-serif;transition:opacity .2s;"
                            onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        🗑️ Confirmer la suppression
                    </button>
                </form>
                <?php else: ?>
                <button disabled style="padding:.65rem 1.4rem;border-radius:8px;background:#e5e7eb;color:#9ca3af;border:none;font-size:.875rem;font-weight:700;cursor:not-allowed;">
                    🗑️ Suppression impossible
                </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>