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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voir utilisateur – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.view-grid { display:grid; grid-template-columns:300px 1fr; gap:1.5rem; align-items:start; }

.profile-card {
    background:var(--white); border:1px solid var(--border);
    border-radius:var(--radius); overflow:hidden;
}
.profile-cover {
    height:80px;
    background:linear-gradient(135deg, var(--ocean-teal), #6366f1);
}
.profile-body { padding:0 1.4rem 1.4rem; text-align:center; }
.profile-av-wrap { margin-top:-36px; margin-bottom:.8rem; }
.profile-av {
    width:72px; height:72px; border-radius:50%;
    background:var(--color-info-bg); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:1.6rem; font-weight:700;
    border:4px solid var(--white); margin:0 auto;
    overflow:hidden;
}
.profile-av img { width:100%; height:100%; object-fit:cover; }
.profile-name  { font-size:1.05rem; font-weight:700; color:var(--text); }
.profile-email { font-size:.8rem; color:var(--text-light); margin-top:3px; }
.profile-badge {
    display:inline-flex; align-items:center; padding:4px 14px;
    border-radius:50px; font-size:.75rem; font-weight:600; margin-top:.6rem;
}
.badge-admin { background:rgba(192,38,211,.1); color:#c026d3; }
.badge-user  { background:#f1f3f5; color:#495057; }

.profile-actions { display:flex; gap:.6rem; justify-content:center; margin-top:1.1rem; }

.info-section { margin-bottom:1.4rem; }
.info-section:last-child { margin-bottom:0; }
.info-section-title {
    font-size:.73rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:var(--text-light);
    padding-bottom:.6rem; border-bottom:1px solid var(--border);
    margin-bottom:.8rem;
}
.info-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:.65rem 0; border-bottom:1px solid var(--border); font-size:.845rem;
}
.info-row:last-child { border-bottom:none; }
.info-key { color:var(--text-light); display:flex; align-items:center; gap:.5rem; }
.info-val { font-weight:600; color:var(--text); font-family:'DM Mono',monospace; font-size:.82rem; text-align:right; }

.points-bar-wrap { margin-top:.4rem; }
.points-bar {
    height:6px; border-radius:3px;
    background:var(--border); overflow:hidden; margin-top:6px;
}
.points-fill {
    height:100%; border-radius:3px;
    background:linear-gradient(90deg, #f59e0b, #f97316);
    transition:width .6s ease;
}

@media(max-width:900px){
    .view-grid { grid-template-columns:1fr; }
    .admin-sidebar { display:none; }
    .admin-main { margin-left:0; }
}
</style>
</head>
<body>
<div class="admin-shell">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Voir utilisateur</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="users.php" style="color:var(--text-light);text-decoration:none;">Users</a>
                    <span>/</span> #<?= $id ?>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">
        <div class="page-header">
            <div>
                <h1>Profil utilisateur</h1>
                <p><?= htmlspecialchars($user['name']) ?> · #<?= $id ?></p>
            </div>
            <div style="display:flex;gap:.6rem;">
                <a href="edit-user.php?id=<?= $id ?>"
                   style="padding:.6rem 1.2rem;border-radius:8px;background:var(--ocean-teal);color:#fff;text-decoration:none;font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;">
                    ✏️ Modifier
                </a>
                <a href="users.php"
                   style="padding:.6rem 1.2rem;border-radius:8px;border:1px solid var(--border);color:var(--text);text-decoration:none;font-size:.85rem;font-weight:500;display:inline-flex;align-items:center;gap:.4rem;">
                    ← Retour
                </a>
            </div>
        </div>

        <div class="view-grid">

            <!-- LEFT — Profile card -->
            <div>
                <div class="profile-card">
                    <div class="profile-cover"></div>
                    <div class="profile-body">
                        <div class="profile-av-wrap">
                            <?php
                            $avatarFile = $user['avatar'] ?? 'default.png';
                            $avatarPath = '../../uploads/avatars/' . $avatarFile;
                            ?>
                            <div class="profile-av">
                                <?php if ($avatarFile !== 'default.png' && file_exists($avatarPath)): ?>
                                    <img src="<?= htmlspecialchars($avatarPath) ?>" alt="avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                        <span class="profile-badge <?= $user['role']==='admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= $user['role']==='admin' ? '👑 Administrateur' : '🌊 Utilisateur' ?>
                        </span>

                        <!-- Points bar -->
                        <?php $pts = (int)$user['loyalty_points']; $max = 20000; $pct = min(100, round($pts/$max*100)); ?>
                        <div class="points-bar-wrap" style="margin-top:1rem;text-align:left;">
                            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-light);">
                                <span>⭐ Points fidélité</span>
                                <span style="font-weight:700;color:var(--text);"><?= number_format($pts) ?></span>
                            </div>
                            <div class="points-bar">
                                <div class="points-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div style="font-size:.72rem;color:var(--text-light);margin-top:4px;"><?= $pct ?>% du niveau maximum</div>
                        </div>

                        <div class="profile-actions">
                            <a href="edit-user.php?id=<?= $id ?>"
                               style="flex:1;padding:.55rem;border-radius:8px;background:var(--ocean-teal);color:#fff;text-decoration:none;font-size:.8rem;font-weight:600;text-align:center;">
                                ✏️ Modifier
                            </a>
                            <a href="delete-user.php?id=<?= $id ?>"
                               style="padding:.55rem .8rem;border-radius:8px;background:rgba(239,68,68,.08);color:#ef4444;border:1px solid rgba(239,68,68,.2);text-decoration:none;font-size:.8rem;">
                                🗑️
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT — Détails -->
            <div class="card" style="padding:1.4rem;">

                <!-- Infos personnelles -->
                <div class="info-section">
                    <div class="info-section-title">👤 Informations personnelles</div>
                    <div class="info-row">
                        <span class="info-key">🆔 ID</span>
                        <span class="info-val">#<?= $user['id'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">👤 Nom</span>
                        <span class="info-val"><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">📧 Email</span>
                        <span class="info-val"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">📞 Téléphone</span>
                        <span class="info-val"><?= $user['phone'] ? htmlspecialchars($user['phone']) : '—' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">🎭 Rôle</span>
                        <span class="info-val">
                            <span class="profile-badge <?= $user['role']==='admin' ? 'badge-admin' : 'badge-user' ?>" style="margin:0;">
                                <?= $user['role']==='admin' ? '👑 Admin' : '🌊 User' ?>
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Compte -->
                <div class="info-section">
                    <div class="info-section-title">📋 Informations du compte</div>
                    <div class="info-row">
                        <span class="info-key">📅 Date d'inscription</span>
                        <span class="info-val"><?= date('d/m/Y à H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">🖼️ Avatar</span>
                        <span class="info-val"><?= htmlspecialchars($user['avatar'] ?? 'default.png') ?></span>
                    </div>
                </div>

                <!-- Fidélité -->
                <div class="info-section">
                    <div class="info-section-title">⭐ Fidélité</div>
                    <div class="info-row">
                        <span class="info-key">⭐ Points accumulés</span>
                        <span class="info-val" style="color:#f59e0b;font-size:1rem;"><?= number_format($user['loyalty_points']) ?> pts</span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">📊 Niveau</span>
                        <span class="info-val">
                            <?php
                            $pts = (int)$user['loyalty_points'];
                            if ($pts >= 15000)     echo '<span style="color:#f59e0b;">🥇 Gold</span>';
                            elseif ($pts >= 5000)  echo '<span style="color:#9ca3af;">🥈 Silver</span>';
                            elseif ($pts >= 1000)  echo '<span style="color:#cd7f32;">🥉 Bronze</span>';
                            else                   echo '<span style="color:#6b7280;">Débutant</span>';
                            ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>